<?php

namespace App\Packages\LC;


class LCManager
{
	private static $_instance = null;
	private static $_api_key = null;

	private function __construct()
	{
		// decode api key
		self::$_api_key = "";

	}

	public static function getInstance()
	{
		if (self::$_instance == null)
		{
			self::$_instance = new LCManager();
		}

		return self::$_instance;
	}

	public function check(string $EL, $ship_cache=false)
	{
		static $cache = [
			"tsm" => 0
		];

		if (time() - $cache["tsm"] < 86400 && !$ship_cache) // 24h
			return true;

		if (self::_check($EL))
		{
			$cache["tsm"] = time();
			return true;
		}

		return false;
	}

	private static function _check(string $EL)
	{
		$host = isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : "" ;

	    $ch = curl_init();
	    $agents = [
	        "Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36",
	        "Mozilla/5.0 (Linux; Android 6.0.1; SM-G935S Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36",
	        "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/13.2b11866 Mobile/16A366 Safari/605.1.15",
	        "Mozilla/5.0 (iPhone9,3; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1",
	    ];

	    $agent = $agents[array_rand($agents)];

	    curl_setopt_array($ch, [
		    CURLOPT_URL => base64_decode("aHR0cDovL2xpY2Vuc2UubXI0d2ViLmNvbS9hcGkvY2hlY2tfbGljZW5zZS8K"),
		    CURLOPT_SSL_VERIFYPEER => false,
		    CURLOPT_SSL_VERIFYHOST => 0,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_TIMEOUT => 20,
		    CURLOPT_POST => true,
		    CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POSTFIELDS => [
				base64_decode("bGljZW5zZV9jb2RlCg==") => $EL,
				"host" => $host
			],
		    CURLOPT_HTTPHEADER => [
		    	"Authorization: Bearer " . self::$_api_key,
		        "User-Agent: ". $agent
		    ]
	    ]);

	    $output = json_decode(curl_exec($ch), true);
	    curl_close($ch);

	    if (isset($output[base64_decode("c3RhdHVzCg==")]) && $output[base64_decode("c3RhdHVzCg==")] == base64_decode("QUNUSVZFCg=="))
	    	return 1 == (2-1*100/(5*20));
	    return (5+6+5+3-9*8/6+6+9+1-0-7) == 1;
	}
}

?>
