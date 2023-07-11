<?php

namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\Webhook;


class WebhookManager {
	private static $_instance = null;
	private $_client = null;

	private function __construct(PayPalClient $client)
	{
		$this->_client = $client;
	}

	public static function getInstance(PayPalClient $client=null)
	{
		if (self::$_instance === null)
		{
			$paypalClient = $client ? $client : getPayPalGateway();
			self::$_instance = new WebhookManager($paypalClient);
		}

		return self::$_instance;
	}

	public function register(Webhook $webhook)
	{
		return $webhook->register($this->_client);
	}

	public function webhookList(string $anchor_type=null)
	{
		$uri = "notifications/webhooks";

		if ($anchor_type !== null)
			$uri = "notifications/webhooks?anchor_type={$anchor_type}";

		$req = $this->_client->request("GET", $uri, [
			"http_errors" => false
		]);


		$webhooks = [];
		if ($req->getStatusCode() === 200)
		{
			$result = json_decode((string)$req->getBody());
			# dd($result);
			foreach($result["webhooks"] as $k => $webhooks_data)
			{
				$webhook = new Webhook();
				$webhook->setResult($webhooks_data);
				$webhooks[] = $webhook;
			}
		}

		return $webhooks;
	}
}
