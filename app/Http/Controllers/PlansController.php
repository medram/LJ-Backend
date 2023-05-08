<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;


class PlansController extends Controller
{

    public function list()
    {
        $plans = Plan::where('active', 1)->orderBy('id', 'DESC')->get();

        return response()->json([
            'errors' => false,
            'plans' => $plans
        ]);
    }
}
