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

	public function createChatRoom($filePath, $return_raw_response=false)
	{
		$req = $this->_askpdfClient->client->request("POST", "upload", [
			'http_errors' => false,
			'multipart' => [
			        [
			            'name'     => 'file',
			            'contents' => fopen($filePath, 'r'),
			            'filename' => basename($filePath)
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

		return null;
	}
}
