<?php

use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan as PayPalPlan;
use App\Packages\AskPDF\AskPDFClient;
use App\Packages\AskPDF\ChatManager;

use App\Models\Setting;


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

// Create a PayPal plan from a db_plan.
function getOrCreatePaypalPlan($db_plan)
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

// Get Demo status
function isDemo()
{
	return isset($_ENV['DEMO_MODE']) ? (in_array($_ENV['DEMO_MODE'], ["1", 1, "true"]) ? true : false) : false;
}

// Return app version.
function getAppVersion()
{
	return "1.1.0";
}

// Return installation status.
function isInstalled()
{
	return in_array(env("INSTALLED"), ["1", 1, "true"]) ? true : false;
}
