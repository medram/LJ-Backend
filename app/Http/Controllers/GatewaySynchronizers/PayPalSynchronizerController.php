<?php

namespace App\Http\Controllers\GatewaySynchronizers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;
use App\Http\Controllers\GatewaySynchronizers\BaseGatewaySynchronizer;

class PayPalSynchronizerController extends BaseGatewaySynchronizer
{
    public function sync(Request $request)
    {
        $request->validate([
            "PM_PAYPAL_CLIENT_ID" 		=> "string|required",
            "PM_PAYPAL_CLIENT_SECRET" 	=> "string|required",
            "PM_PAYPAL_STATUS" 			=> "boolean|required",
            "PM_PAYPAL_SANDBOX" 		=> "boolean|required",
        ]);

        // ## Sync with PayPal Gateway ##
        // Remove old plans/subscriptions/products from the old PayPal api
        $paypal = getPayPalGateway();
        $webhookManager = WebhookManager::getInstance();

        # Get all active plans
        $db_plans = Plan::where([
            "status" 		=> 1,
            "soft_delete" 	=> 0,
            "is_free"		=> 0
        ])->get();

        // Deactivate all old PayPal plans
        foreach ($db_plans as $db_plan) {
            if ($db_plan->paypal_plan_id) {
                $plan = $paypal->getPlanById($db_plan->paypal_plan_id);
                if ($plan && $plan->status == "ACTIVE") {
                    $plan->deactivate();
                }
            }
        }

        // Get all current PayPal subscription
        $db_subscriptions = Subscription::where([
            "payment_gateway" 	=> "PAYPAL",
            "status" 			=> 1
        ])->get();

        // Cancel all old PayPal subscriptions
        foreach ($db_subscriptions as $key => $db_sub) {
            $subscription = $paypal->getSubscriptionById($db_sub->gateway_subscription_id);
            if ($subscription) {
                try {
                    $subscription->cancel();
                } catch (\Exception $e) {
                    // It's fine, Do nothing.
                }
            }
        }

        // Remove old webhooks
        $webhook_id = getSetting("PM_PAYPAL_WEBHOOK_ID");

        if ($webhook_id) {
            $webhook = $webhookManager->getWebhookById($webhook_id);

            if ($webhook) {
                $webhookManager->delete($webhook);
            }
        }

        $data = $request->json()->all();
        // Save the new PayPal API keys into the DB
        setSetting("PM_PAYPAL_CLIENT_ID", $data["PM_PAYPAL_CLIENT_ID"]);
        setSetting("PM_PAYPAL_CLIENT_SECRET", $data["PM_PAYPAL_CLIENT_SECRET"]);
        setSetting("PM_PAYPAL_STATUS", $data["PM_PAYPAL_STATUS"]);
        setSetting("PM_PAYPAL_SANDBOX", $data["PM_PAYPAL_SANDBOX"]);

        // PayPalGateway & webhookManager MUST use the new PayPal API keys.
        $paypal = getPayPalGateway(); // refresh
        $webhookManager = WebhookManager::refreshInstance();

        // Create new Product/Plans
        # Get all active plans
        $db_plans = Plan::where([
            "soft_delete" 	=> 0,
            "is_free"		=> 0
        ])->get();

        foreach ($db_plans as $db_plan) {
            // Create PayPal plan & PayPal product (if not exists), and update db_plan with new plan ID.
            $plan = getOrCreatePaypalPlan($db_plan);

            // sync db_plan status
            if ($db_plan->status == 0) {
                $plan->deactivate();
            }
        }

        // Register new webhook
        $paypal_config = config("payment_gateways.paypal");
        $webhook = new Webhook($paypal_config["WEBHOOK_URL"], $paypal_config["WEBHOOK_EVENTS"]);

        if ($webhookManager->register($webhook)) {
            // Update PM_PAYPAL_WEBHOOK_ID
            setSetting("PM_PAYPAL_WEBHOOK_ID", $webhook->id);
        }

        return response()->json([
            "errors" => false,
            "message" => "Saved Successfully."
        ], 201);
    }
}
