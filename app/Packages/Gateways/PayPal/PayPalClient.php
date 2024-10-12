<?php

namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan;
use App\Packages\Gateways\PayPal\Subscription;

class PayPalClient
{
    private $_config = [];
    private $currency = "USD";
    public $client = null;
    //private $builder = null;

    public function __construct($config)
    {
        $defaultConfig = [
            "sandbox" => false,
            "client_id" => "",
            "secret"    => ""
        ];
        $this->_config = $config + $defaultConfig;
        $this->client = new \GuzzleHttp\Client([
            "base_uri" => $this->baseURL(),
            "auth" => [$this->_config["client_id"], $this->_config["secret"]]
        ]);
    }

    public function baseURL()
    {
        if ($this->_config['sandbox'] === false) {
            return "https://api-m.paypal.com/v1/";
        }

        return "https://api-m.sandbox.paypal.com/v1/";
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getProductById(string $id)
    {
        $req = $this->client->request("GET", "catalogs/products/{$id}");

        if ($req->getStatusCode() === 200) {
            $product = new Product();
            $product->setResult($req->getBody());
            return $product;
        }
        return null;
    }

    public function getPlanById(string $id)
    {
        try {
            $req = $this->client->request("GET", "billing/plans/{$id}");

            if ($req->getStatusCode() === 200) {
                $plan = new Plan();
                $plan->setPayPalClient($this);
                $plan->setResult($req->getBody());
                return $plan;
            }
        } catch (\Exception $e) {
            null;
        }

        return null;
    }

    public function getSubscriptionById(string $id)
    {
        $req = $this->client->request("GET", "billing/subscriptions/{$id}");

        if ($req->getStatusCode() === 200) {
            $subscription = new Subscription();
            $subscription->setPayPalClient($this);
            $subscription->setResult($req->getBody());
            return $subscription;
        }
        return null;
    }

    public function productList()
    {
        $req = $this->client->request("GET", "catalogs/products?total_required=true");
        $products = [];

        if ($req->getStatusCode() === 200) {
            $results = json_decode((string)$req->getBody())->products;

            foreach ($results as $product_result) {
                $product = new Product();
                $product->setResult(json_encode($product_result));
                $products[] = $product;
            }

        }

        return $products;
    }
}
