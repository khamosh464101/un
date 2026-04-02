<?php

namespace App\Helpers;
use Carbon\Carbon;

class ExtraHelper {
    

    public static function generateFileName($file)
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Clean filename
        // $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);

        $extension = $file->getClientOriginalExtension();

        // Format: YYMMDD_HHMMSS_milliseconds
        $timestamp = Carbon::now()->format('Y-m-d H-i-s_v');

        return $name . ' ' . $timestamp . '.' . $extension;
    }
}