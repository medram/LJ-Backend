<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Subscription;


class SubscriptionController extends Controller
{
    // List all available Subscriptions.
    public function list(Request $request)
    {
        //$subscriptions = Subscription::orderByDesc('id')->get();
        $subscriptions = Subscription::select("subscriptions.*", "plans.name as plan_name", "plans.price", "plans.billing_cycle", "plans.is_free", "users.email as user_email", "users.username as user_username")
                    ->leftJoin("plans", "subscriptions.plan_id", "=", "plans.id")
                    ->leftJoin("users", "subscriptions.user_id", "=", "users.id")
                    ->orderBy("id", "DESC")
                    ->get();



        return response()->json([
            "errors" => false,
            "subscriptions" => $subscriptions ? $subscriptions : []
        ]);
    }

    // Cancel a specific subscription.
    public function cancel(Request $request, string $sub_id)
    {
        $subscription = Subscription::where("sub_id", $sub_id)->first();

        if ($subscription)
        {
            $subscription->status = Subscription::CANCELED;
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
                    // stripe subscription cancellation.
                    cancelStripeSubscriptionById($subscription->gateway_subscription_id);
                }
            } catch (\Exception $e){
                // just pass
                Log::error($e);
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
