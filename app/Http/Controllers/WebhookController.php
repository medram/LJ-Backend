<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Subscription;
use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;

use Carbon\Carbon;


class WebhookController extends Controller
{
    public function paypal(Request $request)
    {
        $data = $request->json()->all();
        # 1 = active | 0 = expired | 2 = canceled | 3 suspended
        $subscription = Subscription::where("sub_id", $data["resource"]["id"])->orderBy("created_at", "desc")->first();

        $webhookManager = WebhookManager::getInstance();
        $verified = $webhookManager->verifyWebhookSignature($request);

        if ($verified && $subscription)
        {
            if ($data["event_type"] === "BILLING.SUBSCRIPTION.ACTIVATED")
            {
                $subscription->status = 1;
            }
            else if ($data["event_type"] === "BILLING.SUBSCRIPTION.EXPIRED")
            {
                $subscription->status = 0;
            }
            else if ($data["event_type"] === "BILLING.SUBSCRIPTION.CANCELLED")
            {
                $subscription->status = 2;
            }
            else if ($data["event_type"] === "BILLING.SUBSCRIPTION.SUSPENDED")
            {
                $subscription->status = 3;
            }
            else if ($data["event_type"] === "PAYMENT.SALE.COMPLETED")
            {
                $subscription->status = 1;
                $plan = $subscription->plan();

                if ($plan)
                {
                    $duration = $plan->billing_cycle == "monthly"? 30 : 365;
                    $subscription->expiring_at = Carbon::now()->addDays($duration);

                    $subscription->pdfs = $plan->pdfs;
                    $subscription->questions = $plan->questions;
                    $subscription->pdf_size = $plan->pdf_size;
                }
            }

            $subscription->save();
        }
    }

    public function registerPayPalWebhook(Request $request)
    {
        $WEBHOOK_URL = url("api/v1/webhook/paypal");
        $WEBHOOK_EVENTS = [
                "PAYMENT.SALE.COMPLETED",
                "BILLING.SUBSCRIPTION.ACTIVATED",
                "BILLING.SUBSCRIPTION.CANCELLED",
                "BILLING.SUBSCRIPTION.SUSPENDED",
                "BILLING.SUBSCRIPTION.EXPIRED",
                "BILLING.SUBSCRIPTION.PAYMENT.FAILED",
            ];

        $webhookManager = WebhookManager::getInstance();
        $webhook_id = getSetting("PM_PAYPAL_WEBHOOK_ID");
        $webhook = $webhookManager->getWebhookById($webhook_id);

        if ($webhook)
        {
            // update webhook
            $webhook = new Webhook($WEBHOOK_URL, $WEBHOOK_EVENTS, $webhook->id);

            if ($webhookManager->update($webhook))
            {
                return response()->json([
                    "errors" => false,
                    "message" => "PayPal webhook updated"
                ], 201);
            }
        }
        else
        {
            // create webhook
            $webhook = new Webhook($WEBHOOK_URL, $WEBHOOK_EVENTS);

            if ($webhookManager->register($webhook))
            {
                // Update PM_PAYPAL_WEBHOOK_ID
                setSetting("PM_PAYPAL_WEBHOOK_ID", $webhook->id);

                return response()->json([
                    "errors" => false,
                    "message" => "PayPal webhook registered"
                ], 201);
            }
        }


        return response()->json([
            "errors" => true,
            "message" => "Something went wrong during PayPal webhook registration!"
        ], 400);
    }
}
