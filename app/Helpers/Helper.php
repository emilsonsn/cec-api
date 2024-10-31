<?php

namespace App\Helpers;

use App\Models\Setting;

class Helper
{
    public static function getGlobalLimit()
    {
        $settings = Setting::first();

        return $settings->limit ?? 10;
    }

}
