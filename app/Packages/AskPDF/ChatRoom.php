<?php

namespace App\Packages\AskPDF;

use App\Packages\AskPDF\AskPDFClient;


class ChatRoom
{
	public $uuid = "";
	public $chat_history = "";
	public $updated = "";
	public $created = "";

	private $_askpdfClient = null;


	public function __construct(string $uuid)
	{
		$this->uuid = $uuid;
	}

	public function registerClient(AskPDFClient $client)
	{
		$this->_askpdfClient = $client;
	}


	public function clearHistory()
	{
		$req = $this->_askpdfClient->client->request("POST", "chat/clear-history", [
			"json" => [
				"uuid" => $this->uuid
			]
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}

	public function destroy()
	{
		$req = $this->_askpdfClient->client->request("POST", "chat/delete", [
			"json" => [
				"uuid" => $this->uuid
			]
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}

	public function details()
	{
		$req = $this->_askpdfClient->client->request("GET", "chat/{$this->uuid}/detail");

		if ($req->getStatusCode() === 200)
		{
			$result = json_decode($req->getBody());
			# $this->setResult($result);

			# Update object details
			$this->chat_history = $result->chat_history;
			$this->updated = $result->updated;
			$this->created = $result->created;

			return $result;
		}

		return false;
	}

	public function send(string $prompt)
	{
		$req = $this->_askpdfClient->client->request("POST", "chat", [
			"json" => [
				"uuid" 		=> $this->uuid,
				"prompt"	=> $prompt
			]
		]);

		if ($req->getStatusCode() === 200)
		{
			$response = json_decode($req->getBody());
			return $response;
		}

		return null;
	}

}
