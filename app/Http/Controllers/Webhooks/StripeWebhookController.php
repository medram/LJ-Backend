<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Plan;
use App\Packages\Gateways\PayPal\Webhook;
use App\Packages\Gateways\PayPal\WebhookManager;
use App\Http\Controllers\Webhooks\WebhookController;

use Carbon\Carbon;
use Str;


class StripeWebhookController extends WebhookController
{
	// Handle Stripe payments
	public function handle(Request $request)
	{
		$payload = $request->json()->all();
		$stripe = getStripeClient();
		$stripeEvent = $stripe->events->retrieve($payload["id"], []);

		if ($stripeEvent)
		{
			# 1 = active | 0 = expired | 2 = canceled | 3 suspended
			if ($stripeEvent->type == "invoice.paid")
			{
				## Probably extends subscription (create it not existed) and create a new invoice
				$stripeInvoice = $stripeEvent->data->object;
				$stripeSubscription = getStripeSubscriptionById($stripeInvoice->subscription);
				$user = User::find($stripeSubscription->metadata->customer_id);
				$plan = Plan::find($stripeSubscription->metadata->plan_id);

				if ($stripeInvoice && $user && $stripeSubscription && $stripeInvoice->status == "paid")
				{
					$stripe_plan_id = $stripeInvoice->lines->data[0]->price->id;

					// Create a db Payment / invoice
					$invoice = new Invoice();
					$invoice->invoice_id = rand(1000000, 9999999);
					$invoice->user_id = $user->id;
					$invoice->plan_id = $plan->id;
					$invoice->amount = $stripeInvoice->amount_paid / 100;
					$invoice->status = 1; // 1 = paid | 0 = unpaid | 2 = refunded
					$invoice->paid_at = Carbon::parse($stripeSubscription->created);
					$invoice->payment_gateway = "STRIPE"; // PAYPAL | STRIPE
					$invoice->gateway_plan_id = $stripe_plan_id;
					$invoice->gateway_subscription_id = $stripeSubscription->id;

					$invoice->save();

					// Create a new db subscription.
					$subscription = new Subscription();
					$subscription->sub_id = strtoupper(Str::random(10));
					$subscription->user_id = $user->id;
					$subscription->plan_id = $plan->id;
					$subscription->status = Subscription::ACTIVE; // 1 = Active | 0 = expired | 2 = Cancelled

					/*
					if ($plan->billing_cycle == "monthly")
					    $subscription->expiring_at = Carbon::now()->addMonth(); // add one month
					else if ($plan->billing_cycle == "yearly")
					    $subscription->expiring_at = Carbon::now()->addYear(); // add one year
					*/
					$subscription->expiring_at = Carbon::parse($stripeSubscription->current_period_end);

					$subscription->payment_gateway = "STRIPE"; // PAYPAL | STRIPE
					$subscription->gateway_plan_id = $stripe_plan_id;
					$subscription->gateway_subscription_id = $stripeSubscription->id;

					$old_subscription = $user->getCurrentSubscription();
					if ($old_subscription && $old_subscription->isValid())
					{
					    # Add old subscription quota to the new subscription quota.
					    $subscription->pdfs = $plan->pdfs + $old_subscription->pdfs;
					    $subscription->questions = $plan->questions + $old_subscription->questions;
					    $subscription->pdf_size = $plan->pdf_size;

					    // Disable old subscription
					    $old_subscription->status = Subscription::CANCELED;
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
			}
			else if ($stripeEvent->type == "customer.subscription.updated")
			{
				# Update current subscription status & expiration date
				$stripeSubscription = $stripeEvent->data->object;
				$user = User::find($stripeSubscription->metadata->customer_id);

				if ($user)
				{
					$subscription = $user->getCurrentSubscription(); # DB subscription
					if ($subscription)
					{
						# Available stripe subscription statuses: active | paused | trialing | canceled | ended
						if (in_array($stripeSubscription->status, ["active", "trialing"]))
							$subscription->status = Subscription::ACTIVE; # active

						# this most likely won't be triggered, for "canceled" the following event is triggered instead "customer.subscription.deleted"
						else if (in_array($stripeSubscription->status, ["canceled", "ended"]))
							$subscription->status = Subscription::CANCELED; # canceled

						else if ($stripeSubscription->status == "paused")
							$subscription->status = Subscription::SUSPENDED; # suspended

						# Update expiration date
						if ($stripeSubscription->current_period_end)
							$subscription->expiring_at = Carbon::parse($stripeSubscription->current_period_end);

						$subscription->save();
					}
				}
			}
			else if ($stripeEvent->type == "customer.subscription.deleted")
			{
				$stripeSubscription = $stripeEvent->data->object;
				$user = User::find($stripeSubscription->metadata->customer_id);

				if ($user)
				{
					$subscription = $user->getCurrentSubscription();
					if ($subscription)
					{
						$subscription->status = Subscription::CANCELED;

						# Update expiration date
						if ($stripeSubscription->current_period_end)
							$subscription->expiring_at = Carbon::parse($stripeSubscription->current_period_end);

						$subscription->save();
					}

				}
			}
			else if ($stripeEvent->type == "invoice.payment_failed")
			{
				# Stop user/client subscription
				$stripeInvoice = $stripeEvent->data->object;

				if ($stripeInvoice && $stripeInvoice->subscription)
				{
					$stripeSubscription = getStripeSubscriptionById((string)$stripeInvoice->subscription);
					$user = User::find($stripeSubscription->metadata->customer_id);

					if ($user)
					{
						$subscription = $user->getCurrentSubscription();
						if ($subscription)
						{
							$subscription->status = Subscription::EXPIRED;
							$subscription->save();
						}
					}
				}

				# TODO: notify the client (probably via an email message)
				# $user->sendBillingNotificationEmail();
			}
			# Will be triggered 3 days before a subscription ends by stripe
			else if ($stripeEvent->type == "customer.subscription.trial_will_end")
			{
				# TODO: notify the client (probably via an email message)
				# $user->sendBillingNotificationEmail();
			}

		}
	}
}
