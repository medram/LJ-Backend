<?php

namespace App\Packages\AskPDF;


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
		$chatRoom = new ChatRoom($uuid);
		$chatRoom->registerClient($this->_askpdfClient);
		# Sync data
		if ($chatRoom->details())
			return $chatRoom;
		return null;
	}
}
