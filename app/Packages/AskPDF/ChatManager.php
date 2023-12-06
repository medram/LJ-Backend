<?php

namespace App\Packages\AskPDF;

use App\Models\Chat;


class ChatManager {
	private $_chats = [];
	private static $_instance = null;
	private $_askpdfClient = null;

	public function __construct()
	{
		$this->_askpdfClient = getAskPDFClient();
	}


	public function getInstance()
	{
		if (self::$_instance == null)
			self::$_instance = new ChatManager();

		return self::$_instance;
	}

	public function getChatRoomByUUID(string $uuid)
	{
		$chat = Chat::where("uuid", $uuid)->get()->first();

		if ($chat)
		{
			$chatRoom = new ChatRoom($uuid);
			$chatRoom->registerClient($this->_askpdfClient);

			# Sync data
			if ($chatRoom->details())
				return $chatRoom;
		}
		return null;
	}

	public function createChatRoom($file, $return_raw_response=false)
	{
		$req = $this->_askpdfClient->client->request("POST", "upload", [
			'http_errors' => false,
			'multipart' => [
			        [
			            'name'     => 'file',
			            'contents' => $file->get(),
			            'filename' => basename($file->getClientOriginalName())
			        ]
			    ]
		]);

		if ($req->getStatusCode() === 201)
		{
			$response = json_decode($req->getBody());
			if ($return_raw_response)
				return $response;
			# return a chat room
			$chatRoom = new ChatRoom($response->uuid);
			$chatRoom->registerClient($this);
			return $chatRoom;
		}
		else if ($req->getStatusCode() === 422)
		{
			$response = json_decode($req->getBody());

			return throw new \Exception($response->detail);
		}

		return null;
	}

	public function updateAIModelSettings(array $payload)
	{
		$req = $this->_askpdfClient->client->request("POST", "ai-model-settings/update", [
			'http_errors' => false,
			'json' => $payload,
		]);

		if ($req->getStatusCode() === 204)
			return true;
		return false;
	}
}
