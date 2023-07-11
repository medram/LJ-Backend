<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;


class WebhookController extends Controller
{
    public function paypal(Request $request)
    {

    }

    public function registerPayPalWebhook(Request $request)
    {
        $webhookManager = WebhookManager::getInstance();
        $webhook = new Webhook(url("api/v1/webhook/paypal"), [
            "BILLING.SUBSCRIPTION.CANCELLED",
            "BILLING.SUBSCRIPTION.SUSPENDED",
            "BILLING.SUBSCRIPTION.EXPIRED",
            "BILLING.SUBSCRIPTION.PAYMENT.FAILED",
        ]);

        if ($webhookManager->register($webhook))
        {
            return response()->json([
                "errors" => false,
                "message" => "PayPal webhook registered"
            ], 201);
        }

        return response()->json([
            "errors" => true,
            "message" => "Something went wrong during PayPal webhook registration!"
        ], 400);
    }
}
