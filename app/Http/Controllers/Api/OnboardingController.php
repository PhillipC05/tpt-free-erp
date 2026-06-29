<?php

namespace App\Http\Controllers\Api;

use App\Models\Finance\Account;
use App\Models\HR\Department;
use App\Models\OnboardingCompletion;
use App\Models\OnboardingPreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function presets(): JsonResponse
    {
        $presets = OnboardingPreset::all();

        return $this->respond([
            'success' => true,
            'data' => $presets,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $completion = OnboardingCompletion::where('user_id', $request->user()->id)->first();

        if (! $completion) {
            return $this->respond([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'industry_key' => null,
                    'completed_at' => null,
                ],
            ]);
        }

        $status = 'pending';
        if ($completion->completed_at) {
            $status = 'completed';
        } elseif ($completion->skipped_at) {
            $status = 'skipped';
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'status' => $status,
                'industry_key' => $completion->industry_key,
                'completed_at' => $completion->completed_at,
            ],
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'industry_key' => 'required|exists:onboarding_presets,industry_key',
        ]);

        if ($error) {
            return $error;
        }

        $preset = OnboardingPreset::where('industry_key', $request->input('industry_key'))->first();

        // Seed chart of accounts from template
        $coa = $preset->chart_of_accounts_template ?? [];
        foreach ($coa as $accountData) {
            $code = $accountData['code'] ?? null;
            if ($code && ! Account::where('code', $code)->exists()) {
                Account::create([
                    'code' => $code,
                    'name' => $accountData['name'] ?? $code,
                    'type' => $accountData['type'] ?? 'asset',
                    'category' => $accountData['category'] ?? null,
                    'description' => $accountData['description'] ?? null,
                    'is_active' => true,
                    'currency' => $accountData['currency'] ?? 'NZD',
                    'opening_balance' => $accountData['opening_balance'] ?? 0,
                    'current_balance' => $accountData['opening_balance'] ?? 0,
                ]);
            }
        }

        // Seed departments from template
        $departments = $preset->departments_template ?? [];
        foreach ($departments as $deptData) {
            $name = $deptData['name'] ?? null;
            if ($name && ! Department::where('name', $name)->exists()) {
                Department::create([
                    'name' => $name,
                    'code' => $deptData['code'] ?? strtoupper(substr($name, 0, 4)),
                    'description' => $deptData['description'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        // Mark onboarding as completed
        OnboardingCompletion::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'industry_key' => $request->input('industry_key'),
                'completed_at' => now(),
                'skipped_at' => null,
            ]
        );

        return $this->respondSuccess('Onboarding applied successfully', [
            'industry_key' => $request->input('industry_key'),
            'accounts_seeded' => count($coa),
            'departments_seeded' => count($departments),
        ]);
    }

    public function skip(Request $request): JsonResponse
    {
        OnboardingCompletion::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'skipped_at' => now(),
                'completed_at' => null,
                'industry_key' => null,
            ]
        );

        return $this->respondSuccess('Onboarding skipped');
    }

    public function reset(Request $request): JsonResponse
    {
        OnboardingCompletion::where('user_id', $request->user()->id)->delete();

        return $this->respondSuccess('Onboarding reset. Redirect to /onboarding to re-run.');
    }
}
