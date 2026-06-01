<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseApiController
{
    private const ALLOWED_KEYS = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'default_currency',
        'timezone',
        'fiscal_year_start',
        'date_format',
    ];

    public function index(): JsonResponse
    {
        return $this->respondSuccess('Settings retrieved', Setting::all_as_map());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->only(self::ALLOWED_KEYS);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return $this->respondSuccess('Settings saved', Setting::all_as_map());
    }
}
