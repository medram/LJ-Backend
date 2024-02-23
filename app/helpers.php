<?php

use Illuminate\Support\Facades\Log;

use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan as PayPalPlan;
use App\Packages\AskPDF\AskPDFClient;
use App\Packages\AskPDF\ChatManager;

use App\Models\Setting;
use App\Models\Plan;


// Get all available website settings.
function getAllSettings()
{
	return Setting::getAllSettings() + ["APP_VERSION" => getAppVersion()];
}

// Get indevidual website setting.
function getSetting(string $key)
{
	if ($key === "APP_VERSION")
		return getAppVersion();
	return Setting::getSetting($key);
}

// Update website setting.
function setSetting(string $key, $value)
{
	return Setting::where("name", $key)->update([
		"value" => $value
	]);
}

// Get user token.
function userToken($request)
{
	return trim(str_ireplace("Bearer ", "", $request->header('Authorization')));
}

function getOrCreateStripePlan(Plan $db_plan, bool $force_create = false)
{
	$stripe = getStripeClient();

	if ($db_plan->stripe_plan_id && !$force_create)
	{
		try {
			return getStripePlanById($db_plan->stripe_plan_id);
		} catch (\Stripe\Exception\InvalidRequestException $e){
			# Do nothing is fine
			Log::error($e);
		}
	}

	// Create a Stripe Plan
	$stripeProduct = getStripeProduct();

	$cycle = $db_plan->billing_cycle === "monthly"? "month" : "year";

	$stripePlan = createStripePlan([
		"product" 		=> $stripeProduct->id,
		"billing_scheme" => "per_unit",
		"currency" 		=> strtolower(getSetting("CURRENCY")),
		"unit_amount" 	=> $db_plan->price * 100,
		"recurring" 	=> [
			"interval" => $cycle,
			# "trial_period_days" => 15, // days
		],
	]);

	if ($stripePlan)
	{
		$db_plan->stripe_plan_id = $stripePlan->id;
		$db_plan->save();
	}

	return $stripePlan;
}


function getStripePlanById(string $id)
{
	$stripe = getStripeClient();
	return $stripe->prices->retrieve($id, []);
}

function updateStripePlan(string $id, array $data)
{
	$stripe = getStripeClient();
	return $stripe->prices->update($id, $data);
}

function createStripePlan(array $data)
{
	$stripe = getStripeClient();
	return $stripe->prices->create($data);
}

function getStripeSubscriptionById(string $id)
{
	$stripe = getStripeClient();
	return $stripe->subscriptions->retrieve($id, []);
}

function cancelStripeSubscriptionById(string $id)
{
	$stripe = getStripeClient();
	return $stripe->subscriptions->cancel($id, []);
}

function registerStripeWebhook()
{
	$stripe = getStripeClient();
	$stripe_webhook_url = config("payment_gateways.stripe.WEBHOOK_URL");
	$events = config("payment_gateways.stripe.WEBHOOK_EVENTS");

	# Delete the old webhook
	if (getSetting("PM_STRIP_WEBHOOK_ID"))
	{
		try {
			$stripe->webhookEndpoints->delete(getSetting("PM_STRIP_WEBHOOK_ID"), []);
		} catch (\Exception $e){
			// Do nothing is fine
			Log::warning($e);
		}
	}

	# Create a new webhook
	$webhook = $stripe->webhookEndpoints->create([
	  'url' => $stripe_webhook_url,
	  'enabled_events' => $events,
	]);

	# Save the new webhook id into the db
	setSetting("PM_STRIP_WEBHOOK_ID", $webhook->id);
	return $webhook;
}

function getStripeWebhook(bool $refresh = false)
{
	static $webhook = null;

	if ($webhook == null || $refresh)
	{
		if (getSetting("PM_STRIP_WEBHOOK_ID"))
		{
			# Retrieve
			$stripe = getStripeClient();
			$webhook = $stripe->webhookEndpoints->retrieve(getSetting("PM_STRIP_WEBHOOK_ID"), []);
		}
		else
		{
			# Create a webhook
			$webhook = registerStripeWebhook();
		}
	}

	return $webhook;
}

function deleteStripeWebhook(string $id)
{
	setSetting("PM_STRIP_WEBHOOK_ID", ""); # delete the webhook from db

	$stripe = getStripeClient();
	return $stripe->webhookEndpoints->delete($id, []);;
}

function getStripeProduct(bool $refresh = false)
{
	// TODO: Create a Stripe product
	$stripe = getStripeClient();
	static $product = null;
	$stripe_product_id = getSetting("PM_STRIP_PRODUCT_ID");

	if ($product != null && !$refresh)
		return $product;

	if ($stripe_product_id && !$refresh) // product exists
		$product = $stripe->products->retrieve($stripe_product_id, []);
	else
	{
		// create product
		$product = $stripe->products->create(['name' => getSetting("SITE_NAME")." service"]);
		// Save the product ID into the DB
		setSetting("PM_STRIP_PRODUCT_ID", $product->id);
	}

	return $product;
}

// Create a PayPal plan from a db_plan.
function getOrCreatePaypalPlan(Plan $db_plan)
{
	$paypal = getPayPalGateway();
	$paypalPlan = null;

	// Get PayPal plan
	if ($db_plan->paypal_plan_id)
	{
	    $paypalPlan = $paypal->getPlanById($db_plan->paypal_plan_id);
	}

	if (!$paypalPlan)
	{
		// Create a PayPal Product
		$product = null;

		if (getSetting('PM_PAYPAL_PRODUCT_ID'))
		{
			$product = $paypal->getProductById(getSetting('PM_PAYPAL_PRODUCT_ID'));
		}

		if (!$product)
		{
			$product_name = getSetting('SITE_NAME') . " service";
			$product = new Product(["name" => $product_name, "type" => "SERVICE"]);
			$product->setPayPalClient($paypal);
			$product->setup();

			if ($product)
				setSetting("PM_PAYPAL_PRODUCT_ID", $product->id);
		}

		// Create a PayPal Plan
		$paypalPlan = new PayPalPlan();
		$paypalPlan->setPayPalClient($paypal)
		    ->setName($db_plan->name)
		    ->setProductById($product->id);
		    //->addTrial('DAY', 7)
		if ($db_plan->billing_cycle === "monthly")
		    $paypalPlan->addMonthlyPlan($db_plan->price, 0);
		else if ($db_plan->billing_cycle === "yearly")
		    $paypalPlan->addYearlyPlan($db_plan->price, 0);

		$paypalPlan->setup(); # required to register it to PayPal.

		// Update db_plan (save PayPal plan ID).
		$db_plan->paypal_plan_id = $paypalPlan->id;
		$db_plan->save();
	}

	return $paypalPlan;
}

// Get available PayPal gateway.
function getPayPalGateway()
{
	$config = [
	    "sandbox" => getSetting("PM_PAYPAL_SANDBOX"),
	    "client_id" => getSetting("PM_PAYPAL_CLIENT_ID"),
	    "secret"    => getSetting("PM_PAYPAL_CLIENT_SECRET")
	];

	$paypal = new PayPalClient($config);
	$paypal->setCurrency(getSetting("CURRENCY"));
	return $paypal;
}

// Get RapidAPI client instance.
function getAskPDFClient()
{
	static $askpdfClient = null;

	$config = [
		"RAPID_API_KEY" 	=> getSetting("RAPID_API_KEY"),
		"RAPID_API_HOST"	=> getSetting("RAPID_API_HOST")
	];

	if ($askpdfClient == null)
		$askpdfClient = new AskPDFClient($config);

	return $askpdfClient;
}

// Get chat Manager instance.
function getChatManager()
{
	static $chatManager = null;

	if ($chatManager == null)
		$chatManager = new ChatManager();

	return $chatManager;
}

function getStripeClient(bool $refresh = false)
{
	static $client = null;

	if ($client == null || $refresh)
	{
		$secret_key = getSetting("PM_STRIP_SECRET_KEY");
		$secret_key_test = getSetting("PM_STRIP_SECRET_KEY_TEST");
		$sandbox = getSetting("PM_STRIP_SANDBOX");

		if ($sandbox)
			$secret_key = $secret_key_test;

		$client = new \Stripe\StripeClient($secret_key);
	}

	return $client;
}

function WebhookLog(string $error_message)
{
	$datetime = date('c');
	$message = "[{$datetime}]: ".$error_message."\n";
	file_put_contents("../storage/logs/webhook.log", $message, FILE_APPEND);
}

// Get Demo status
function isDemo()
{
	return isset($_ENV['DEMO_MODE']) ? (in_array($_ENV['DEMO_MODE'], ["1", 1, "true"]) ? true : false) : false;
}

// Return app version.
function getAppVersion()
{
	return "1.4.0";
}

// Return installation status.
function isInstalled()
{
	return in_array(env("INSTALLED"), ["1", 1, "true"]) ? true : false;
}

