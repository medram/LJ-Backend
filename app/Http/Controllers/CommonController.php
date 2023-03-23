<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;


class CommonController extends Controller
{
    public function settings(Request $request)
    {
        $settings = getAllSettings();

        // TODO: hide sensitive data.
        $filtered_settings['SITE_NAME'] = $settings['SITE_NAME'];
        $filtered_settings['TIMEZONE'] = $settings['TIMEZONE'];
        $filtered_settings['CURRENCY'] = $settings['CURRENCY'];
        $filtered_settings['CURRENCY_SYMBOL'] = $settings['CURRENCY_SYMBOL'];

        return response()->json([
            "error" => false,
            "settings" => $filtered_settings
        ]);
    }

    public function plans(Request $request)
    {
        $plans = Plan::where([
            'is_visible' => true,
            'is_active' => true
        ])->get();

        return response()->json([
            'error' => false,
            'plans' => $plans
        ]);
    }
}
