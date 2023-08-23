<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Plan;
use App\Models\Subscription;
use App\Rules\StripTagsRule;


class PlansController extends Controller
{
    // List all available plans.
    public function list()
    {
        //$plans = Plan::where('status', 1)->get();
        $plans = Plan::where("soft_delete", 0)->get();

        return response()->json([
            'errors' => false,
            'plans' => $plans
        ]);
    }

    // Add a new Plan
    public function add(Request $request)
    {
        $request->validate([
            "name" => ["string", "required", "max:50", new StripTagsRule],
            "description" => ["string", "nullable", "max:50", new StripTagsRule],
            "price" => "numeric|min:0",
            "is_popular" => "boolean",
            "is_free" => "boolean",
            "billing_cycle" => Rule::in(['monthly', 'yearly']),
            "status" => "boolean",
            "pdfs" => "integer",
            "pdf_size" => "numeric",
            "pdf_pages" => "integer",
            "questions" => "integer",
            "features" => "string|nullable",
            "paypal_plan_id" => "string|nullable",
            "stripe_plan_id" => "string|nullable"
        ]);

        try {
            $plan = Plan::create($request->all());

            // Create PayPal Plan (if Payment gateway is setup)
            if (getSetting("PM_PAYPAL_CLIENT_ID") && getSetting("PM_PAYPAL_CLIENT_SECRET"))
                getOrCreatePaypalPlan($plan);

            return response()->json([
                'errors' => false,
                'message' => "Create successfully.",
                'plans' => $plan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => true,
                'message' => "Something went wrong!"
            ]);
        }
    }

    // Edit a specific Plan.
    public function edit(Request $request, $id)
    {
        $plan = plan::where(['id' => $id, 'soft_delete' => 0])->get()->first();

        if ($plan)
        {
            $request->validate([
                "name" => ["string", "required", "max:50", new StripTagsRule],
                "description" => ["string", "nullable", "max:50", new StripTagsRule],
                "price" => "numeric|min:0",
                "is_popular" => "boolean",
                "is_free" => "boolean",
                "billing_cycle" => Rule::in(['monthly', 'yearly']),
                "status" => "boolean",
                "pdfs" => "integer",
                "pdf_size" => "numeric",
                "pdf_pages" => "integer",
                "questions" => "integer",
                "features" => "string|nullable",
                "paypal_plan_id" => "string|nullable",
                "stripe_plan_id" => "string|nullable"
            ]);

            try {
                $plan->update($request->all());

                // Update PayPal plan (pricing)

                if ($plan->paypal_plan_id && !$plan->isFree())
                {
                    $paypal = getPayPalGateway();
                    $paypalPlan = $paypal->getPlanById($plan->paypal_plan_id);
                    if ($paypalPlan)
                    {
                        // update paypal plan pricing
                        if ($paypalPlan->getPrice() != $plan->price)
                            $paypalPlan->updatePricing($plan->price);

                        // Update plan status
                        if ($plan->status == 1 && $paypalPlan->status == "INACTIVE")
                        {
                            $paypalPlan->activate();
                        }
                        else if ($plan->status == 0 && $paypalPlan->status == "ACTIVE")
                        {
                            $paypalPlan->deactivate();
                        }
                    }
                    // May need to create a PayPal plan from db_plan (in case plan deleted from PayPal dashboard.)
                }

                return response()->json([
                    'errors' => false,
                    'message' => "Updated successfully.",
                    'plan' => $plan
                ], 200);
            } catch (\Exception $e) {
                echo $e;
                return response()->json([
                    'errors' => true,
                    'message' => "Something went wrong."
                ]);
            }

        }

        return response()->json([
            'errors' => true,
            'message' => "Plan not found."
        ]);
    }

    // Delete plan.
    public function delete(Request $request)
    {
        $id = $request->json("id");
        $plan = Plan::where(['id' => $id, 'soft_delete' => 0])->first();

        if ($plan)
        {
            $plan->update([
                'soft_delete' => 1,
                'status' => 0
            ]);

            if (!$plan->is_free)
            {
                // Deactivate PayPal plan
                $paypal = getPayPalGateway();
                $paypalPlan = $paypal->getPlanById($plan->paypal_plan_id);

                if ($paypalPlan)
                {
                    try
                    {
                        $paypalPlan->deactivate();
                    } catch (\Exception $e) {
                        // Do nothing
                    }
                }

                // Cancel all subscriptions of that plan
                $subscriptions = Subscription::where([
                    "plan_id"   => $plan->id,
                    "status"    => 1, // active subscriptions
                ])->get();

                foreach($subscriptions as $sub)
                {
                    // if it's PayPal subscription
                    if ($sub->payment_gateway == "PAYPAL")
                    {
                        $paypalSubscription = $paypal->getSubscriptionById($sub->gateway_subscription_id);
                        if ($paypalSubscription)
                        {
                            try {
                                $paypalSubscription->cancel();
                            } catch (\Exception $e){
                                // it's fine, do nothing or maybe log it.
                            }
                        }
                    }
                    else if ($sub->payment_gateway == "STRIPE")
                    {
                        // Handle stripe
                    }
                }
            }

            return response()->json([
                'errors' => false,
                'message' => "Deleted successfully."
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => "Something went wrong."
        ]);
    }
}
