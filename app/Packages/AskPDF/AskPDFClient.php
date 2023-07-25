<?php

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
				"Accept"			=> "application/json",
				"X-RapidAPI-Client-Key" => $this->_config["RAPID_API_KEY"],
			]
		]);
	}

	public function getBaseUrl()
	{
		return "https://askpdf1.p.rapidapi.com/api/v1/";
	}

	public function registerOpenAIKey($openai_key)
	{
		$req = $this->client->request("POST", "/openai-key/update", [
			'http_errors' => false
		]);

		if ($req->getStatusCode() === 201)
			return true;

		return false;
	}
}
