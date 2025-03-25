<?php

namespace App\Packages\LC;

class LCManager
{
    private static $_instance = null;
    private $_api_key = null;
    private $_cache_file = null;

    private function __construct()
    {
        $this->_api_key = trim(base64_decode("YjI4YzU0NmIxYzUwOGVlZGIzZWI2M2ZjYWE5MTkyZDUzZDJlYzY1Zgo="));
        $this->_cache_file = __DIR__ . "/cache/r.cache";
    }

    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new LCManager();
        }

        return self::$_instance;
    }

    public function LCInfo()
    {
        $cache = $this->cacheRead();
        return $cache;
    }

    public function LCType()
    {
        return $this->LCInfo()["type"];
    }

    public function cacheRead()
    {
        try {
            return json_decode(trim(base64_decode(file_get_contents($this->_cache_file))), true);
        } catch (\Exception) {
            return [
                "timestamp" => 0,
                "type" => ""
            ];
        }
    }

    public function cacheSave(array $data)
    {
        return file_put_contents($this->_cache_file, base64_encode(json_encode($data)));
    }

    public function check(string $EL = null, $ship_cache = false)
    {
        if ($EL == null) {
            $EL = getSetting(trim(base64_decode("TElDRU5TRV9DT0RFCg==")));
        }

        $cache = $this->cacheRead();

        if ((time() - $cache["timestamp"]) < 86400 && !$ship_cache) { // 24h
            return true;
        }

        $result = self::_check($EL);

        if ($result["status"]) {
            $type = isset($result["output"]["license_type"]) && $result["output"]["license_type"] == base64_decode("RVhURU5ERUQgTElDRU5TRQ==") ? "EL" : "RL";

            $this->cacheSave([
                "timestamp" => time(),
                "type" => $type
            ]);

            return true;
        }

        return false;
    }

    private function _check(string $EL)
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "" ;

        $ch = curl_init();
        $agents = [
            "Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36",
            "Mozilla/5.0 (Linux; Android 6.0.1; SM-G935S Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36",
            "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/13.2b11866 Mobile/16A366 Safari/605.1.15",
            "Mozilla/5.0 (iPhone9,3; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1",
        ];

        $agent = $agents[array_rand($agents)];

        curl_setopt_array($ch, [
            // CURLOPT_URL => trim(base64_decode("aHR0cDovL2xpY2Vuc2UubXI0d2ViLmNvbS9hcGkvY2hlY2tfbGljZW5zZS8K")),
            CURLOPT_URL => 'https://license.mr4web.com/api/check_license/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "license_code={$EL}&host={$host}",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->_api_key,
                "User-Agent: ". $agent
            ]
        ]);

        $output = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($output[trim(base64_decode("c3RhdHVzCg=="))]) && $output[trim(base64_decode("c3RhdHVzCg=="))] == trim(base64_decode("QUNUSVZFCg=="))) {
            return [
                "status" => 1 == (2 - 1 * 100 / (5 * 20)),
                "output" => $output
            ];
        }
        return [
            "status" => (5 + 6 + 5 + 3 - 9 * 8 / 6 + 6 + 9 + 1 - 0 - 7) == 1,
            "output" => $output
        ];
    }
}
