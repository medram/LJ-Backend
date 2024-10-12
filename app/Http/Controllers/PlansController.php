<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
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
            "name" => ["string", "required", "max:50", new StripTagsRule()],
            "description" => ["string", "nullable", "max:50", new StripTagsRule()],
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

            // set "is_free" = true if the price = 0
            if ($plan->price == 0) {
                $plan->is_free = true;
                $plan->save();
            }

            if (!$plan->isFree()) {
                // Create PayPal Plan (if Payment gateway was setup)
                if (getSetting("PM_PAYPAL_CLIENT_ID") && getSetting("PM_PAYPAL_CLIENT_SECRET")) {
                    getOrCreatePaypalPlan($plan);
                }

                // Create Stripe Plan (if Payment gateway was setup)
                if (getSetting("PM_STRIP_SECRET_KEY") && getSetting("PM_STRIP_SECRET_KEY_TEST")) {
                    getOrCreateStripePlan($plan);
                }

            }

            return response()->json([
                'errors' => false,
                'message' => "Create successfully.",
                'plans' => $plan
            ], 201);
        } catch (\Exception $e) {
            Log::error($e);
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

        if ($plan) {
            $request->validate([
                "name" => ["string", "required", "max:50", new StripTagsRule()],
                "description" => ["string", "nullable", "max:50", new StripTagsRule()],
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

                // Update PayPal plan (pricing & status)
                if ($plan->paypal_plan_id && !$plan->isFree()) {
                    $paypal = getPayPalGateway();
                    $paypalPlan = $paypal->getPlanById($plan->paypal_plan_id);
                    if ($paypalPlan) {
                        // update paypal plan pricing
                        if ($paypalPlan->getPrice() != $plan->price) {
                            $paypalPlan->updatePricing($plan->price);
                        }

                        // Update plan status
                        if ($plan->status == 1 && $paypalPlan->status == "INACTIVE") {
                            $paypalPlan->activate();
                        } elseif ($plan->status == 0 && $paypalPlan->status == "ACTIVE") {
                            $paypalPlan->deactivate();
                        }
                    }
                    // May need to create a PayPal plan from db_plan (in case plan deleted from PayPal dashboard.)
                }

                // Update Stripe Plan (pricing & status)
                if ($plan->stripe_plan_id && !$plan->isFree()) {
                    $stripePlan = getStripePlanById($plan->stripe_plan_id);
                    $stripeProduct = getStripeProduct();

                    if ($stripePlan) {
                        $currency = strtolower(getSetting("CURRENCY"));

                        if ($plan->price * 100 == $stripePlan->unit_amount) {
                            # Update just Plan status
                            updateStripePlan($stripePlan->id, ["active" => (bool)$plan->status]);
                        } else {
                            #### the price changed, so let's create a new Plan.
                            # Delete/archive the Plan
                            updateStripePlan($stripePlan->id, ["active" => false]);
                            # Let's create a new Plan
                            $cycle = $plan->billing_cycle === "monthly" ? "month" : "year";

                            $stripePlan = createStripePlan([
                                "active"            => !!$plan->status,
                                "product"           => $stripeProduct->id,
                                "billing_scheme"    => "per_unit",
                                "currency"          => $currency,
                                "unit_amount"       => $plan->price * 100,
                                "recurring"         => [
                                    "interval" => $cycle,
                                ],
                            ]);

                            # Update the db_plan
                            if ($stripePlan) {
                                $plan->stripe_plan_id = $stripePlan->id;
                                $plan->save();
                            }
                        }
                    }

                }
                return response()->json([
                    'errors' => false,
                    'message' => "Updated successfully.",
                    'plan' => $plan
                ], 200);
            } catch (\Exception $e) {
                Log::error($e);
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

        if ($plan) {
            $plan->update([
                'soft_delete' => 1,
                'status' => 0
            ]);

            if (!$plan->isFree()) {
                // Deactivate PayPal plan
                if ($plan->paypal_plan_id) {
                    $paypal = getPayPalGateway();
                    $paypalPlan = $paypal->getPlanById($plan->paypal_plan_id);

                    if ($paypalPlan) {
                        try {
                            $paypalPlan->deactivate();
                        } catch (\Exception $e) {
                            // Doing nothing is fine
                            Log::error("PAYPAL_ERROR: ".$e->getMessage());
                        }
                    }
                }

                // Delete Stripe Plan (or make as archive)
                if ($plan->stripe_plan_id) {
                    # Delete/archive the Plan
                    updateStripePlan($plan->stripe_plan_id, ["active" => false]);
                }

                // Cancel all subscriptions of that plan
                $subscriptions = Subscription::where([
                    "plan_id"   => $plan->id,
                    "status"    => 1, // active subscriptions
                ])->get();

                foreach ($subscriptions as $sub) {
                    // if it's PayPal subscription
                    if ($sub->payment_gateway == "PAYPAL") {
                        $paypalSubscription = $paypal->getSubscriptionById($sub->gateway_subscription_id);
                        if ($paypalSubscription) {
                            try {
                                $paypalSubscription->cancel();
                            } catch (\Exception $e) {
                                // it's fine, do nothing or maybe log it.
                                Log::error($e);
                            }
                        }
                    } elseif ($sub->payment_gateway == "STRIPE") {
                        // Cancel all stripe subscriptions for this plan
                        try {
                            cancelStripeSubscriptionById($sub->gateway_subscription_id);
                        } catch (\Exception $e) {
                            // it's fine, do nothing or maybe log it.
                            Log::error($e);
                        }
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
