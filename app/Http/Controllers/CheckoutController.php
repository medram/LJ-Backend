<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Str;


use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan as PayPalPlan;
use App\Packages\Gateways\PayPal\Subscription as PayPalSubscription;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Invoice;



class CheckoutController extends Controller
{
    // Create a Gateway Subscription for that spesific order
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
                    "message" => "The selected plan / subscription not available!"
                ], 404);

            }
            // TODO: if it's a normal order.
        }
        else if ($data->gateway == "STRIPE")
        {
            // Do the same for stripe.
            if ($data->type == "subscription")
            {
                $plan = Plan::where(['status' => 1, 'soft_delete' => 0, 'id' => $data->id])->first();
                if ($plan)
                    return $this->createStripeSubscription($plan);

                return response()->json([
                    "errors" => true,
                    "message" => "The selected plan / subscription not available!"
                ], 404);

            }
        }
    }

    // Validate Payment
    public function validateSubscription(Request $request, string $id, int $user_id) # id of a db Plan
    {
        $request->validate([
            "subscription_id" => "string|required"
        ]);

        $subscription_id = $request->input("subscription_id");
        $plan = Plan::where(["id" => $id, "soft_delete" => 0])->first();
        $user = User::where(["id" => $user_id])->first();

        $existed = Subscription::where("gateway_subscription_id", $subscription_id)->first();

        if (!$existed && $plan && $user)
        {
            $paypal = getPayPalGateway();
            $paypalSubscription = $paypal->getSubscriptionById($subscription_id);

            if ($paypalSubscription && $paypalSubscription->status == "ACTIVE")
            {
                // Create a db Payment / invoice
                $invoice = new Invoice();
                $invoice->invoice_id = rand(1000000, 9999999);
                $invoice->user_id = $user->id;
                $invoice->plan_id = $plan->id;
                $invoice->amount = $plan->price;
                $invoice->status = 1; // 1 = paid | 0 = unpaid | 2 = refunded
                $invoice->paid_at = Carbon::parse($paypalSubscription->create_time);
                $invoice->payment_gateway = "PAYPAL"; // PAYPAL | STRIPE
                $invoice->gateway_plan_id = $paypalSubscription->plan_id;
                $invoice->gateway_subscription_id = $paypalSubscription->id;

                $invoice->save();

                // Create a new db subscription foreach Gateway Payment Method.
                $subscription = new Subscription();
                $subscription->sub_id = strtoupper(Str::random(10));
                $subscription->user_id = $user->id;
                $subscription->plan_id = $plan->id;
                $subscription->status = 1; // 1 = Active | 0 = expired | 2 = Cancelled

                if ($plan->billing_cycle == "monthly")
                    $subscription->expiring_at = Carbon::now()->addMonth(); // add one month
                else if ($plan->billing_cycle == "yearly")
                    $subscription->expiring_at = Carbon::now()->addYear(); // add one year

                $subscription->payment_gateway = "PAYPAL"; // PAYPAL | STRIPE
                $subscription->gateway_plan_id = $paypalSubscription->plan_id;
                $subscription->gateway_subscription_id = $paypalSubscription->id;

                $old_subscription = $user->getCurrentSubscription();
                if ($old_subscription && $old_subscription->isValid())
                {
                    # Add old subscription quota to the new subscription quota.
                    $subscription->pdfs = $plan->pdfs + $old_subscription->pdfs;
                    $subscription->questions = $plan->questions + $old_subscription->questions;
                    $subscription->pdf_size = $plan->pdf_size;

                    // Disable old subscription
                    $old_subscription->status = 0;
                    $old_subscription->save();
                }
                else
                {
                    $subscription->pdfs = $plan->pdfs;
                    $subscription->questions = $plan->questions;
                    $subscription->pdf_size = $plan->pdf_size;
                }

                $subscription->save();

                return redirect("/thank-you?t=sub&ref={$invoice->invoice_id}");
            }
        }

        return redirect("/pricing");
    }

    // Create PayPal subscription
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

        if ($db_plan->paypal_plan_id)
        {
            $paypal = getPayPalGateway();
            //$paypalPlan = getOrCreatePaypalPlan($db_plan);
            $paypalPlan = $paypal->getPlanById($db_plan->paypal_plan_id);

            if (!$paypalPlan)
            {
                return response()->json([
                    "errors" => true,
                    "message" => "Invalid PayPal Subscription Plan!"
                ], 400);
            }
            else if ($paypalPlan->status != "ACTIVE")
            {
                return response()->json([
                    "errors" => true,
                    "message" => "Inactive PayPal Subscription Plan!"
                ], 400);
            }

            // Create a PayPal Subscription
            $subscription = new PayPalSubscription();
            $subscription->setPayPalClient($paypal)
                        ->setBrandName(getSetting("SITE_NAME"))
                        ->setPlanById($paypalPlan->id)
                        ->setNoShipping()
                        //->setAutoRenewal()
                        ->addReturnAndCancelUrl(
                            url("/checkout/validate/subscription/{$db_plan->id}/{$user->id}/"),
                            url("/pricing")
                        )
                        ->setSubscriber($user->email, $user->username)
                        ->setup();

            return response()->json([
                "errors" => false,
                "gateway_id"   => $subscription->id,
                "gateway_link" => $subscription->getSubscriptionLink()
            ]);
        }
    }

    public function createStripeSubscription(Plan $plan)
    {
        $user = request()->user();
        $db_plan = $plan;

        if (!getSetting("PM_STRIP_STATUS"))
        {
            return response()->json([
                "errors" => true,
                "message" => "Invalid Payment method"
            ], 400);
        }

        // Get Stripe Plan
        $stripePlan = getOrCreateStripePlan($db_plan);

        if (!$stripePlan)
        {
            return response()->json([
                "errors" => true,
                "message" => "Invalid Stripe Subscription Plan!"
            ], 400);
        }
        else if ($stripePlan->active != true)
        {
            return response()->json([
                "errors" => true,
                "message" => "Inactive Stripe Subscription Plan!"
            ], 400);
        }

        // Create a Stripe Subscription
        $stripe = getStripeClient();
        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => [[
              'price' => $stripePlan->id,
              'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => url("/checkout/validate/subscription/{$db_plan->id}/{$user->id}/"),
            'cancel_url' => url("/pricing"),
          ]);

        $subscription_link = $checkout_session->url;

        echo "Link: ".$subscription_link;

        return response()->json([
            "errors" => false,
            "gateway_id"   => $stripePlan->id, // Subscription ID
            "gateway_link" => $subscription_link
        ]);

    }
}
