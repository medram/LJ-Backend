<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan as PayPalPlan;
use App\Packages\Gateways\PayPal\Subscription;

use App\Models\Plan;
use Illuminate\Validation\Rule;


class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "gateway"   => Rule::in(['PAYPAL', 'STRIPE']), # PAYPAL | STRIPE
            "type"      => Rule::in(['subscription', 'order']),
            "id"        => "integer|required"
        ]);

        $data = (object)$request->json()->all();

        if ($data->gateway == "PAYPAL")
        {
            if ($data->type == "subscription")
            {
                $plan = Plan::where(['status' => 1, 'soft_delete' => 0, 'id' => $data->id])->first();
                if ($plan)
                    return $this->createPayPalSubscription($plan);

                return response()->json([
                    "errors" => true,
                    "message" => "Plan Not Found!"
                ], 404);

            }
            // TODO: if it's a normal order.
        }
        else if ($data->gateway == "STRIPE")
        {
            // TODO: do the same for stripe.
        }
    }

    public function createPayPalSubscription(Plan $plan)
    {
        $user = request()->user();
        $db_plan = $plan;

        if (!getSetting("PM_PAYPAL_STATUS"))
        {
            return response()->json([
                "errors" => true,
                "message" => "Invalid Payment method"
            ], 400);
        }

        //$db_plan = Plan::where(['id' => $id, 'status' => 1, 'soft_delete' => 0])->get()->first();

        if (!$db_plan)
        {
            return response()->json([
                "errors" => true,
                "message" => "The selected plan / subscription not available!"
            ], 400);
        }

        if ($db_plan->paypal_plan_id)
        {
            $paypal = getPayPalGateway();
            //$paypalPlan = getOrCreatePaypalPlan($db_plan);
            $paypalPlan = $paypal->getPlanById($db_plan->paypal_plan_id);

            if (!$paypalPlan)
            {
                return response()->json([
                    "errors" => true,
                    "message" => "PayPal Subscription Plan invalid!"
                ], 400);
            }
            else if ($paypalPlan->status != "ACTIVE")
            {
                return response()->json([
                    "errors" => true,
                    "message" => "PayPal Subscription Plan inactive!"
                ], 400);
            }

            // Create a PayPal Subscription
            $subscription = new Subscription();
            $subscription->setPayPalClient($paypal)
                        ->setBrandName(getSetting("SITE_NAME"))
                        ->setPlanById($paypalPlan->id)
                        ->setNoShipping()
                        //->setAutoRenewal()
                        ->addReturnAndCancelUrl(url("/checkout/validate/{$db_plan->id}"), url("/pricing"))
                        ->setSubscriber($user->email, $user->username)
                        ->setup();

            return response()->json([
                "errors" => false,
                "subscription_id"   => $subscription->id,
                "subscription_link" => $subscription->getSubscriptionLink()
            ]);
        }


    }
}
