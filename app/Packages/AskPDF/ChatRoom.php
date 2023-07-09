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
		$req = $this->_askpdfClient->client->request("POST", "chat/{$this->uuid}/clear-history", [
			'http_errors' => false
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}

	public function destroy()
	{
		$req = $this->_askpdfClient->client->request("DELETE", "chat/{$this->uuid}/delete", [
			'http_errors' => false
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}

	public function details()
	{
		$req = $this->_askpdfClient->client->request("GET", "chat/{$this->uuid}/detail", [
			'http_errors' => false
		]);

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
		$req = $this->_askpdfClient->client->request("POST", "chat/{$this->uuid}", [
			"json" => [
				"prompt" => $prompt
			],
			'http_errors' => false
		]);

		if ($req->getStatusCode() === 200)
		{
			$response = json_decode($req->getBody());
			return $response;
		}

		return null;
	}

	public function stopAgent()
	{
		// TODO: stoping the chat room agent
		$req = $this->_askpdfClient->client->request("POST", "chat/{$this->uuid}/stop", [
			'http_errors' => false
		]);

		if ($req->getStatusCode() === 204)
		{
			return true;
		}

		return false;
	}

}
