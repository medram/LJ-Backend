<?php

namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\WrapperMixin;
use App\Packages\Gateways\PayPal\PayPalClient;


class Webhook extends WrapperMixin {

	private $_url = "";
	private $_events = [];

	public function __construct(string $url, Array $events, string $id = null)
	{
		parent::__construct();

		$this->_url = $url;
		if (count($events) === 0)
			throw new Exception("Events are required for webhooks");

		$this->_events = $events;
		if ($id)
			$this->setID($id);
	}

	public function setID(string $id)
	{
		# the same as: $this->_result->id = $id;
		$this->id = $id;
	}

	public function register(PayPalClient $paypalGateway)
	{
		# dd(json_encode($this->_build_json_data()));

		$req = $paypalGateway->client->request("POST", "notifications/webhooks", [
			"http_errors" => false,
			"json" => $this->_build_json_data()
		]);

		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
			return true;
		}
		return false;
	}

	public function update(PayPalClient $paypalGateway)
	{
		$req = $paypalGateway->client->request("PATCH", "notifications/webhooks/{$this->id}", [
			"http_errors" => false,
			"json" => $this->_build_update_json_data()
		]);

		if ($req->getStatusCode() == 200)
		{
			$this->setResult((string)$req->getBody());
			return true;
		}
		return false;
	}

	public function delete(PayPalClient $paypalGateway)
	{
		$req = $paypalGateway->client->request("DELETE", "notifications/webhooks/{$this->id}", [
			"http_errors" => false,
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}

	private function _build_json_data()
	{
		$payload = [
			"url" => $this->getURL(),
			"event_types" => []
		];

		foreach($this->getEvents() as $k => $event)
		{
			$payload["event_types"][] = ["name" => $event];
		}

		return $payload;
	}

	private function _build_update_json_data()
	{
		$payload = [
			[
				"op" 	=> "replace",
				"path" 	=> "/url",
				"value" => "{$this->getURL()}"
			],
			[
				"op" 	=> "replace",
				"path" 	=> "/event_types",
				"value" => []
			]
		];

		foreach($this->getEvents() as $k => $event)
		{
			$payload[1]["value"][] = ["name" => $event];
		}

		return $payload;
	}

	public function getURL()
	{
		if ($this->_url)
			return $this->_url;
		return $this->url;
	}


	public function getEvents()
	{
		if ($this->_events)
			return $this->_events;

		// Get event names from _result
		$events = [];
		foreach($this->event_types as $event)
			$events[] = $event->name;

		return $events;
	}
}
