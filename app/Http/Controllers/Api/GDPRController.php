<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GDPRController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function exportData(Request $request): JsonResponse
    {
        $user = auth()->user();

        $data = [
            'account' => $user->only(['id', 'name', 'email', 'created_at']),
            'exported_at' => now()->toIso8601String(),
        ];

        return $this->respondSuccess('Data export prepared', $data);
    }

    public function requestErasure(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'reason' => 'nullable|string',
            'confirm' => 'required|boolean|accepted',
        ]);
        if ($error) return $error;

        return $this->respondSuccess('Erasure request submitted. You will be notified once processed.');
    }

    public function requestRectification(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'field' => 'required|string',
            'current_value' => 'required|string',
            'correct_value' => 'required|string',
            'reason' => 'nullable|string',
        ]);
        if ($error) return $error;

        return $this->respondSuccess('Rectification request submitted. You will be notified once processed.');
    }

    public function consents(): JsonResponse
    {
        return $this->respondSuccess('Consents retrieved', [
            'marketing' => false,
            'analytics' => true,
            'third_party' => false,
        ]);
    }

    public function withdrawConsent(string $type): JsonResponse
    {
        $validTypes = ['marketing', 'analytics', 'third_party'];

        if (!in_array($type, $validTypes)) {
            return $this->respondError('Invalid consent type', 422);
        }

        return $this->respondSuccess("Consent for '{$type}' withdrawn successfully");
    }
}
