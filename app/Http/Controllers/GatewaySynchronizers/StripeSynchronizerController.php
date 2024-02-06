<?php

namespace App\Http\Controllers\GatewaySynchronizers;

use Illuminate\Http\Request;

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
				// TODO: deactivate old stripe plans
			}
		}

		// Get all current Stripe subscription
		$db_subscriptions = Subscription::where([
			"payment_gateway" 	=> "STRIPE",
			"status" 			=> 1
		])->get();

		// Cancel all old Stripe subscriptions
		foreach ($db_subscriptions as $key => $db_sub)
		{
			// TODO: Cancel all stripe subscriptions
			$subscription = null; // get all stripe subscriptions

			if ($subscription)
			{
				try {
					$subscription->cancel();
				} catch (\Exception $e){
					// It's fine, Do nothing.
				}
			}
		}

		// Remove old webhooks
		$webhook_id = getSetting("PM_STRIP_WEBHOOK_ID");

		if ($webhook_id)
		{
			// TODO: Delete stripe webhook
		}

		$data = $request->json()->all();
		// Save the new Stripe API keys into the DB
		setSetting("PM_STRIP_SECRET_KEY", $data["PM_STRIP_SECRET_KEY"]);
		setSetting("PM_STRIP_SECRET_KEY_TEST", $data["PM_STRIP_SECRET_KEY_TEST"]);
		setSetting("PM_STRIP_STATUS", $data["PM_STRIP_STATUS"]);
		setSetting("PM_STRIP_SANDBOX", $data["PM_STRIP_SANDBOX"]);

		// MUST use the new for Stripe Client.
		$stripe = getStripeClient(); // TODO: refresh

		// Create new Product/Plans
		# Get all active plans
		$db_plans = Plan::where([
			"soft_delete" 	=> 0,
			"is_free"		=> 0
		])->get();

		foreach($db_plans as $db_plan)
		{
			// Create PayPal plan & PayPal product (if not exists), and update db_plan with new plan ID.
			$plan = getOrCreateStripePlan($db_plan);

			// sync db_plan status
/*			if ($db_plan->status == 0)
				$plan->deactivate();*/
		}

		// TODO: Register new webhook

/*	    if ($webhookManager->register($webhook))
	    {
	        // Update PM_PAYPAL_WEBHOOK_ID
	        setSetting("PM_PAYPAL_WEBHOOK_ID", $webhook->id);
	    }*/

	    return response()->json([
	    	"errors" => false,
	    	"message" => "Saved Successfully."
	    ], 201);
	}
}
