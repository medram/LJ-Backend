<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\User;
use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;
use App\Http\Controllers\Webhooks\WebhookController;
use Carbon\Carbon;

class PayPalWebhookController extends WebhookController
{
    // PayPal webhook
    public function handle(Request $request)
    {
        $payload = json_decode(file_get_contents("php://input"), false); # PayPal payload
        $paypal = getPayPalGateway();
        $webhookManager = WebhookManager::getInstance();
        $verified = $webhookManager->verifyWebhookSignature($request);

        # WebhookLog($payload->event_type . " Called");

        if ($verified) {
            if ($payload->event_type === "BILLING.SUBSCRIPTION.ACTIVATED") {
                # $paypalSubscription = $paypal->getSubscriptionById($payload->resource->id);
                $subscription = Subscription::where("gateway_subscription_id", $payload->resource->id)->first();
                if ($subscription) {
                    $subscription->status = Subscription::ACTIVE;
                    $subscription->save();
                    # WebhookLog("Subscription: active");
                }
            } elseif ($payload->event_type === "BILLING.SUBSCRIPTION.EXPIRED") {
                # $paypalSubscription = $paypal->getSubscriptionById($payload->resource->id);
                $subscription = Subscription::where("gateway_subscription_id", $payload->resource->id)->first();
                if ($subscription) {
                    $subscription->status = Subscription::EXPIRED;
                    $subscription->save();
                    # WebhookLog("Subscription: expired");
                }
            } elseif ($payload->event_type === "BILLING.SUBSCRIPTION.CANCELLED") {
                # $paypalSubscription = $paypal->getSubscriptionById($payload->resource->id);
                $subscription = Subscription::where("gateway_subscription_id", $payload->resource->id)->first();
                if ($subscription) {
                    $subscription->status = Subscription::CANCELED;
                    $subscription->save();
                    # WebhookLog("Subscription: canceled");
                }
            } elseif ($payload->event_type === "BILLING.SUBSCRIPTION.SUSPENDED") {
                # $paypalSubscription = $paypal->getSubscriptionById($payload->resource->id);
                $subscription = Subscription::where("gateway_subscription_id", $payload->resource->id)->first();
                if ($subscription) {
                    $subscription->status = Subscription::SUSPENDED;
                    $subscription->save();
                    # WebhookLog("Subscription: suspended");
                }
            } elseif ($payload->event_type === "PAYMENT.SALE.COMPLETED") {
                // Do Nothing
            }

        }
    }
}
