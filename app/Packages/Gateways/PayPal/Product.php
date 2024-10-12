<?php

namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\WrapperMixin;
use App\Packages\Gateways\PayPal\PayPalClient;

class Product extends WrapperMixin
{
    private $paypal = null;
    private $_data = [
        "name"	=> "",
        "type"	=> "" // SERVICE | PHYSICAL | DIGITAL
    ];

    public function __construct(array|null $config = null)
    {
        parent::__construct();

        if (is_array($config) && $config != null) {
            $this->_data = $config + $this->_data;
        }
    }

    public function setPayPalClient(PayPalClient $paypal)
    {
        $this->paypal = $paypal;
        return $this;
    }

    public function setup()
    {
        $req = $this->paypal->client->request("POST", "catalogs/products", ["json" => $this->_data]);
        if ($req->getStatusCode() == 201) {
            $this->setResult((string)$req->getBody());
        }
        return $this;
    }
}
