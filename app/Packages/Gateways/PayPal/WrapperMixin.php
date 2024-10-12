<?php

namespace App\Packages\Gateways\PayPal;

abstract class WrapperMixin
{
    private $_result = null;

    public function __construct()
    {
        $this->_result = new \stdClass();
    }

    public function __get($key)
    {
        if (isset($this->_result->$key)) {
            return $this->_result->$key;
        }
        return null;
    }

    public function __set($key, $value)
    {
        $this->_result->$key = $value;
    }

    public function setResult($result)
    {
        $this->_result = json_decode((string)$result);
    }

    public function getResult()
    {
        return $this->_result;
    }
}
