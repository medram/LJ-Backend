<?php

namespace App\Http\Controllers\GatewaySynchronizers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Setting;
use App\Models\Plan;
use App\Models\Subscription;

use App\Http\Controllers\GatewaySynchronizers\BaseGatewaySynchronizer;


class StripeSynchronizerController extends BaseGatewaySynchronizer
{
	public function sync(Request $request)
	{
		$request->validate([
			"PM_STRIP_SECRET_KEY" 		=> "string|required",
			"PM_STRIP_SECRET_KEY_TEST" 	=> "string|required",
			"PM_STRIP_STATUS" 			=> "boolean|required",
			"PM_STRIP_SANDBOX" 			=> "boolean|required",
		]);

		// ## Sync with PayPal Gateway ##
		// Remove old plans/subscriptions/products from the old PayPal api
		if ((getSetting("PM_STRIP_SECRET_KEY_TEST") && getSetting("PM_STRIP_SANDBOX"))
				|| (getSetting("PM_STRIP_SECRET_KEY") && !getSetting("PM_STRIP_SANDBOX")))
		{
			$stripe = getStripeClient();

			# Get all active plans
			$db_plans = Plan::where([
				"status" 		=> 1,
				"soft_delete" 	=> 0,
				"is_free"		=> 0
			])->get();

			// Deactivate all old Stripe plans
			foreach ($db_plans as $db_plan)
			{
				if ($db_plan->stripe_plan_id)
				{
					// deactivate old stripe plans
					updateStripePlan($db_plan->stripe_plan_id, ["active" => false]);
				}
			}

			// Get all current Stripe subscription
			$db_subscriptions = Subscription::where([
				"payment_gateway" 	=> "STRIPE",
				"status" 			=> Subscription::ACTIVE
			])->get();

			// Cancel all old Stripe subscriptions
			foreach ($db_subscriptions as $key => $db_sub)
			{
				// Cancel all stripe subscriptions
				try {
					cancelStripeSubscriptionById($db_sub->gateway_subscription_id);
				} catch (\Exception $e){
					// It's fine, Do nothing.
				}
			}

			// Remove old webhooks
			$webhook_id = getSetting("PM_STRIP_WEBHOOK_ID");

			if ($webhook_id)
			{
				// Delete stripe webhook
				deleteStripeWebhook($webhook_id);
			}
		}

		$data = $request->json()->all();
		// Save the new Stripe API keys into the DB
		setSetting("PM_STRIP_SECRET_KEY", $data["PM_STRIP_SECRET_KEY"]);
		setSetting("PM_STRIP_SECRET_KEY_TEST", $data["PM_STRIP_SECRET_KEY_TEST"]);
		setSetting("PM_STRIP_STATUS", $data["PM_STRIP_STATUS"]);
		setSetting("PM_STRIP_SANDBOX", $data["PM_STRIP_SANDBOX"]);

		// MUST use the new for Stripe Client.
		Setting::clear(); 					# Force to use the new saved stripe keys from db
		$stripe = getStripeClient(true); 	# refresh stripe client

		try {
			$product_id = getStripeProduct(true); 	# refresh product id
		} catch (\Stripe\Exception\ApiErrorException $e) {
			return response()->json([
				"errors" => true,
				"message" => "Stripe sync error: Invalid Stripe Key!"
			], 400);
		}

		// Create new Product/Plans
		# Get all active plans
		$db_plans = Plan::where([
			"soft_delete" 	=> 0,
			"is_free"		=> 0
		])->get();

		foreach($db_plans as $db_plan)
		{
			// Create Stripe plan & Stripe product (if not exists), and update db_plan with new plan ID.
			$stripePlan = getOrCreateStripePlan($db_plan);
		}

		// Register new webhook
		try {
		    registerStripeWebhook();
		} catch (\Stripe\Exception\InvalidRequestException $e){
			if (strpos($e->getMessage(), "Invalid URL: URL must be publicly accessible") === false)
			{
				throw $e;
			}
		}

	    return response()->json([
	    	"errors" => false,
	    	"message" => "Saved Successfully."
	    ], 201);
	}
}
