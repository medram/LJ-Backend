<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan as PayPalPlan;
use App\Packages\Gateways\PayPal\Subscription;

use App\Models\Plan;


class CheckoutController extends Controller
{
    public function getPayPalSubscriptionId(Request $request, $id) # plan id.
    {
        $user = $request->user();

        if (!getSetting("PM_PAYPAL_STATUS"))
        {
            return response()->json([
                "errors" => true,
                "message" => "Invalid Payment method"
            ], 400);
        }

        $db_plan = Plan::where(['id' => $id, 'status' => 1, 'soft_delete' => 0])->get()->first();

        if (!$db_plan)
        {
            return response()->json([
                "errors" => true,
                "message" => "The selected plan / subscription not available!"
            ], 400);
        }

        $paypal = getPayPalGateway();
        $paypalPlan = getOrCreatePaypalPlan($db_plan);

        // Create a PayPal Subscription
        $subscription = new Subscription();
        $subscription->setPayPalClient($paypal)
                    ->setBrandName(getSetting("SITE_NAME"))
                    ->setPlanById($paypalPlan->id)
                    ->setNoShipping()
                    //->setAutoRenewal()
                    ->addReturnAndCancelUrl("http://localhost:7000/thank-you", "http://localhost:3000/pricing")
                    ->setSubscriber($user->email, $user->username)
                    ->setup();

        return response()->json([
            "errors" => false,
            "subscription_id"   => $subscription->id,
            "subscription_link" => $subscription->getSubscriptionLink()
        ]);
    }
}
