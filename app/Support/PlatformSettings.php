<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformSettings
{
    public static function values(): array
    {
        return array_replace([
            'platform_name' => 'DairyNex',
            'support_email' => '',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'notice' => '',
            'sms_alerts' => false,
            'whatsapp_alerts' => false,
            'welcome_email' => false,
        ], Schema::hasTable('platform_settings')
            ? DB::table('platform_settings')
                ->pluck('value', 'key')
                ->map(fn ($value) => json_decode($value, true))
                ->all() : []);
    }
}
