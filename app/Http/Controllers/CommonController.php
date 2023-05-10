<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;


class CommonController extends Controller
{
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
