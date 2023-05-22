<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Subscription;


class SubscriptionController extends Controller
{
    public function list(Request $request)
    {
        //$subscriptions = Subscription::orderByDesc('id')->get();
        $subscriptions = Subscription::select("subscriptions.*", "plans.name as plan_name", "plans.price", "plans.billing_cycle", "plans.is_free", "users.email as user_email", "users.username as user_username")
                    ->leftJoin("plans", "subscriptions.plan_id", "=", "plans.id")
                    ->leftJoin("users", "subscriptions.user_id", "=", "users.id")
                    ->get();



        return response()->json([
            "errors" => false,
            "subscriptions" => $subscriptions ? $subscriptions : []
        ]);
    }

    public function cancel(Request $request, string $sub_id)
    {
        $subscription = Subscription::where("sub_id", $sub_id)->first();

        if ($subscription)
        {
            $subscription->status = 2; # 2 = canceled
            $subscription->save();

            try {
                // Cancel Subscription from Payment Gateways as well.
                if ($subscription->payment_gateway == "PAYPAL")
                {
                    $paypal = getPayPalGateway();
                    $paypalSubscription = $paypal->getSubscriptionById($subscription->gateway_subscription_id);
                    $paypalSubscription->cancel();
                }
                else if ($subscription->payment_gateway == "STRIPE")
                {
                    // TODO: stripe subscription cancellation.
                }
            } catch (\Exception $e){
                // just pass
            }

            return response()->json([
                "errors" => false,
                "message" => "Cancelled Successfully."
            ], 200);
        }

        return response()->json([
            "errors" => true,
            "message" => "Subscription not found!"
        ], 404);
    }
}
