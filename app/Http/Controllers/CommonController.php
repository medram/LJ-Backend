<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;


class CommonController extends Controller
{
    public function plans(Request $request)
    {
        $plans = Plan::where([
            'status' => 1,
            'soft_delete' => 0
        ])->get();

        return response()->json([
            'error' => false,
            'plans' => $plans
        ]);
    }

    public function paymentMethods(Request $request)
    {
        $payment_mothods = [];

        if (getSetting("PM_PAYPAL_STATUS") == true)
        {
            $payment_mothods[] = [
                "name"      => "PayPal",
                "type"      => "PAYPAL",
                "key"       => getSetting("PM_PAYPAL_CLIENT_ID"),
                "sandbox"   => getSetting("PM_PAYPAL_SANDBOX"),
            ];
        }

        if (getSetting("PM_STRIP_STATUS") == true)
        {
            $payment_mothods[] = [
                "name"      => "Stripe",
                "type"      => "STRIPE",
                "key"       => getSetting("PM_STRIP_PUBLIC_KEY"),
                "sandbox"   => getSetting("PM_STRIP_SANDBOX"),
            ];
        }

        return response()->json([
            "errors" => false,
            "payment_methods" => $payment_mothods
        ]);
    }
}
