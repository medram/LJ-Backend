<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;
use App\Http\Controllers\Webhooks\WebhookController;

use Carbon\Carbon;


class PayPalWebhookController extends WebhookController
{
    // PayPal webhook
    public function handle(Request $request)
    {
        $payload = (object)$request->json()->all(); # PayPal payload
        # 1 = active | 0 = expired | 2 = canceled | 3 suspended
        $paypal = getPayPalGateway();
        $webhookManager = WebhookManager::getInstance();
        $verified = $webhookManager->verifyWebhookSignature($request);

        if ($verified)
        {
            $paypalSubscription = $paypal->getSubscriptionById($subscription_id);
            $subscription = null; // db subscription

            if ($paypalSubscription)
            {
                if ($payload->event_type === "BILLING.SUBSCRIPTION.ACTIVATED")
                {
                    $subscription->status = Subscription::ACTIVE;
                    $subscription->save();
                }
                else if ($payload->event_type === "BILLING.SUBSCRIPTION.EXPIRED")
                {
                    $subscription->status = Subscription::EXPIRED;
                    $subscription->save();
                }
                else if ($payload->event_type === "BILLING.SUBSCRIPTION.CANCELLED")
                {
                    $subscription->status = Subscription::CANCELED;
                    $subscription->save();
                }
                else if ($payload->event_type === "BILLING.SUBSCRIPTION.SUSPENDED")
                {
                    $subscription->status = Subscription::SUSPENDED;
                    $subscription->save();
                }
                else if ($payload->event_type === "PAYMENT.SALE.COMPLETED" && $paypalSubscription->status == "ACTIVE")
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
                }
                /*
                else if ($payload->event_type === "PAYMENT.SALE.COMPLETED")
                {
                    $subscription->status = Subscription::ACTIVE;
                    $plan = $subscription->plan;

                    if ($plan)
                    {
                        $user = $subscription->user;

                        if ($user)
                        {
                            // create an invoice
                            $invoice = new Invoice();
                            $invoice->invoice_id = rand(1000000, 9999999);
                            $invoice->user_id = $user->id;
                            $invoice->plan_id = $plan->id;
                            $invoice->amount = $plan->price;
                            $invoice->status = 1; // 1 = paid | 0 = unpaid | 2 = refunded
                            $invoice->paid_at = Carbon::now();
                            $invoice->payment_gateway = "PAYPAL"; // PAYPAL | STRIPE
                            $invoice->gateway_plan_id = $subscription->gateway_plan_id;
                            $invoice->gateway_subscription_id = $subscription->gateway_subscription_id;

                            $invoice->save();
                        }

                        $duration = $plan->billing_cycle == "monthly"? 30 : 365;
                        $subscription->expiring_at = Carbon::now()->addDays($duration);

                        $subscription->pdfs = $plan->pdfs;
                        $subscription->questions = $plan->questions;
                        $subscription->pdf_size = $plan->pdf_size;
                    }
                }
                */
            }


        }
    }
}
