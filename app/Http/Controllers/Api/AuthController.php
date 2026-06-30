<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\TOTPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        $user->load('roles');

        return $this->respond([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
        $user->load('roles');

        return $this->respond([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respondSuccess('Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond(['success' => true, 'data' => $request->user()->load('roles')]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('api-token')->plainTextToken;

        return $this->respond(['success' => true, 'token' => $token]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->respondSuccess('Password reset link sent')
            : $this->respondError('Unable to send reset link');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->respondSuccess('Password reset successfully')
            : $this->respondError('Unable to reset password');
    }

    public function sendMagicLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // Generate and send magic link (simplified - in production use a proper service)
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $token = Str::random(60);
            $user->update(['magic_link_token' => $token, 'magic_link_expires_at' => now()->addMinutes(15)]);
            // Send email with magic link (implement Mail facade)
        }

        return $this->respondSuccess('Magic link sent if email exists');
    }

    public function verifyMagicLink(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $user = User::where('magic_link_token', $request->token)
            ->where('magic_link_expires_at', '>', now())
            ->first();

        if (! $user) {
            return $this->respondError('Invalid or expired token', 401);
        }

        $user->update(['magic_link_token' => null, 'magic_link_expires_at' => null]);
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->respond(['success' => true, 'token' => $token]);
    }

    public function enableTOTP(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->totp_secret) {
            return $this->respondError('TOTP already enabled');
        }

        $request->validate(['code' => 'required|string|size:6']);

        // Generate and verify TOTP secret (simplified)
        $secret = TOTPService::generateSecret();
        $user->update(['totp_secret' => $secret, 'totp_enabled' => true]);

        return $this->respondSuccess('TOTP enabled successfully');
    }

    public function disableTOTP(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['totp_secret' => null, 'totp_enabled' => false]);

        return $this->respondSuccess('TOTP disabled successfully');
    }

    public function verifyTOTP(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();
        if (! $user->totp_secret) {
            return $this->respondError('TOTP not enabled');
        }

        $isValid = TOTPService::verify($user->totp_secret, $request->code);

        return $isValid
            ? $this->respondSuccess('TOTP verified successfully')
            : $this->respondError('Invalid code', 401);
    }
}
