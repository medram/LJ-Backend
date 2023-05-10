<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;


class PlansController extends Controller
{

    public function list()
    {
        //$plans = Plan::where('status', 1)->get();
        $plans = Plan::all();

        return response()->json([
            'errors' => false,
            'plans' => $plans
        ]);
    }
}
