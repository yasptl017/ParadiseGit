<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class AppSettings
{
    public static function value(string $key, $default = null)
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', $key)) {
                return $default;
            }

            $setting = Setting::first();

            return optional($setting)->{$key} ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
