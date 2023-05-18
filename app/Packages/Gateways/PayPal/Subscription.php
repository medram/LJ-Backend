<?php
namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\WrapperMixin;
use App\Packages\Gateways\PayPal\PayPalClient;


class Subscription extends WrapperMixin
{
	private $paypal = null;
	private $_data = [
	    "plan_id" 		=> "",	# Required
	    "auto_renewal" 	=> false,
	    "application_context" => [
	        "shipping_preference" 	=> "NO_SHIPPING",
	        "payment_method" 		=> [
	            "payee_preferred" => "IMMEDIATE_PAYMENT_REQUIRED"
	        ],
	        "return_url" => "", # Required
	        "cancel_url" => ""  # Required
	    ]
	];

	public function __construct(Array|null $config = null)
	{
		if (is_array($config) && $config != null)
			$this->_data = $config + $this->_data;
	}

	public function setPayPalClient(PayPalClient $paypal)
	{
		$this->paypal = $paypal;
		return $this;
	}

	public function setup()
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions", ["json" => $this->_data]);
		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
		}
		return $this;
	}

	public function addReturnAndCancelUrl(string $return_url, string $cancel_url)
	{
		$this->_data["application_context"]["return_url"] = $return_url;
		$this->_data["application_context"]["cancel_url"] = $cancel_url;
		return $this;
	}

	public function setNoShipping()
	{
		$this->_data["application_context"]["shipping_preference"] = "NO_SHIPPING";
		return $this;
	}

	public function setAutoRenewal(bool $auto_renewal = true)
	{
		$this->_data["auto_renewal"] = $auto_renewal;
		return $this;
	}

	public function setBrandName(string $brand_name)
	{
		$this->_data["application_context"]["brand_name"] = $brand_name;
		return $this;
	}

	public function setSubscriber(string $email, string $name=null)
	{
		if (!empty($name))
			$this->_data["subscriber"]["name"]["given_name"] = $name;
		$this->_data["subscriber"]["email_address"] = $email;
		return $this;
	}

	public function setPlanById(string $planId)
	{
		$this->_data["plan_id"] = $planId;
		return $this;
	}

	public function showData()
	{
		return $this->_data;
	}

	public function getSubscriptionLink()
	{
		return isset($this->links[0]) ? $this->links[0]->href : null;
	}

	public function cancel(string $reason="Subscription canceled")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/cancel", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "CANCELLED";
			return true;
		}
		return false;
	}

	public function suspend(string $reason="Item out of stock")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/suspend", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "SUSPENDED";
			return true;
		}
		return false;
	}

	public function activate(string $reason="Reactivating the subscription")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/activate", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "ACTIVE";
			return true;
		}
		return false;
	}
}
