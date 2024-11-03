<?php

namespace App\Packages\AskPDF;

use App\Packages\AskPDF\ChatRoom;

class AskPDFClient
{
    private $_config = [];
    public $client = null;

    public function __construct(array $config)
    {
        $this->_config = $config;

        $this->client = new \GuzzleHttp\Client([
            "base_uri" => $this->getBaseUrl(),
            "headers" => [
                "X-RapidAPI-Key"	=> $this->_config["RAPID_API_KEY"],
                "X-RapidAPI-Host"	=> $this->_config["RAPID_API_HOST"],
                "Accept"			=> "application/json",
                "X-RapidAPI-Client-Key" => hash("sha256", $this->_config["RAPID_API_KEY"]),
            ]
        ]);
    }

    public function getBaseUrl()
    {
        if (env("RAPID_API_URL") && $this->_isValidURL(env("RAPID_API_URL"))) {
            return env("RAPID_API_URL");
        }

        # return the rapid api host if it is a url
        if ($this->_isValidURL($this->_config["RAPID_API_HOST"])) {
            return $this->_config["RAPID_API_HOST"];
        }
        
        return "https://{$this->_config["RAPID_API_HOST"]}/api/v1/";
    }

    public function registerOpenAIKey($openai_key)
    {
        $req = $this->client->request("POST", "/openai-key/update", [
            'http_errors' => false
        ]);

        if ($req->getStatusCode() === 201) {
            return true;
        }

        return false;
    }

    private function _isValidURL(string $url) {
        // Parse the URL
        $parsedUrl = parse_url($url);
        
        // Check if the scheme is http or https and if the host is valid
        if (isset($parsedUrl['scheme']) && in_array($parsedUrl['scheme'], ['http', 'https'])) {
            // Check if the host is valid
            if (isset($parsedUrl['host']) && filter_var($parsedUrl['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                return true; // Valid HTTP/HTTPS URL with a valid domain
            }
        }
        return false; // Invalid URL or domain
    }
    
}
