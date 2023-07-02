<?php

/*$config = [
	"RAPID_API_KEY" 	=> "26c891ee9emsh9e4f1eb317edd6ap1a9dc4jsn3f07ef931f4f",
	"RAPID_API_HOST"	=> "askpdf1.p.rapidapi.com"
];

$askpdfClient = new AskPDFClient($config);
$askpdfClient->registerOpenAIKey($openai_key);
$askpdfClient->createChatRoom($filePath);

$chatRoom = new ChatRoom();
$chatRoom->clearHistory();
$chatRoom->destroy(); # delete
$chatRoom->details();
$chatRoom->send($prompt);*/

namespace App\Packages\AskPDF;

use App\Packages\AskPDF\ChatRoom;


class AskPDFClient {

	private $_config = [];
	public $client = null;

	public function __construct(Array $config)
	{
		$this->_config = $config;

		$this->client = new \GuzzleHttp\Client([
			"base_uri" => $this->getBaseUrl(),
			"headers" => [
				"X-RapidAPI-Key"	=> $this->_config["RAPID_API_KEY"],
				"X-RapidAPI-Host"	=> $this->_config["RAPID_API_HOST"],
				"Accept"			=> "application/json"
			]
		]);
	}

	public function getBaseUrl()
	{
		return "https://askpdf1.p.rapidapi.com/api/v1/";
	}

	public function registerOpenAIKey($openai_key)
	{
		$req = $this->client->request("POST", "/openai-key/update");

		if ($req->getStatusCode() === 201)
			return true;

		return false;
	}

	public function createChatRoom($filePath)
	{
		$req = $this->client->request("POST", "/upload", [
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
			$response = (object)$req->getBody();
			# return a chat room
			$chatRoom = new ChatRoom($response->uuid);
			$chatRoom->registerClient($this);
			return $chatRoom;
		}

		return null;
	}
}
