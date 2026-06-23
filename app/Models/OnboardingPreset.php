<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingPreset extends Model
{
    protected $fillable = [
        'industry_key',
        'industry_name',
        'icon_emoji',
        'description',
        'recommended_modules',
        'chart_of_accounts_template',
        'departments_template',
        'color_theme',
    ];

    protected $casts = [
        'recommended_modules' => 'array',
        'chart_of_accounts_template' => 'array',
        'departments_template' => 'array',
    ];
}
