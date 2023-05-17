<?php

namespace App\Packages\Gateways;

use App\Packages\Gateways\PayPalClient\Registerable;
use App\Packages\Gateways\PayPalClient\Product;
use App\Packages\Gateways\PayPalClient\Plan;
use App\Packages\Gateways\PayPalClient\Subscription;


class PayPalClient
{
	private $_config = [];
	private $currency = "USD";
	public $client = null;
	private $builder = null;

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

		$this->builder = new Builder($this);
	}

	public function getBuilder()
	{
		return $this->builder;
	}

	public function baseURL()
	{
		if ($this->_config['sandbox'] === false)
			return "https://api-m.paypal.com/v1/";

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

	public function register(Registerable $item)
	{
		$item->setPayPalClient($this);
		return $item->setup($this);
	}

	public function getProductById(string $id)
	{
		$req = $this->client->request("GET", "catalogs/products/{$id}" );
		$product = new Product();
		$product->setResult($req->getBody());
		return $product;
	}

	public function getPlanById(string $id)
	{
		$req = $this->client->request("GET", "billing/plans/{$id}" );
		$plan = new Plan();
		$plan->setPayPalClient($this);
		$plan->setResult($req->getBody());
		return $plan;
	}

	public function getSubscriptionById(string $id)
	{
		$req = $this->client->request("GET", "billing/subscriptions/{$id}" );
		$subscription = new Subscription();
		$subscription->setPayPalClient($this);
		$subscription->setResult($req->getBody());
		return $subscription;
	}

}

class Builder
{
	private $paypal_client = null;
	public $product = [];
	private $billing_cycles = [];
	private $cycle_count = 1;
	private $setup_fee = [
		"value" => 0,
		"currency_code" => "USD"
	];
	private $application_context = [
		"brand_name" => "",
        "shipping_preference" => "NO_SHIPPING",
        "payment_method" => [
            "payee_preferred" => "IMMEDIATE_PAYMENT_REQUIRED"
        ],
        "return_url" => "",
        "cancel_url" => ""
	];

	private $subscriber = [
		"name" => [
			"given_name" => ""
		],
		"email_address" => "",
	];

	public function __construct(PayPalClient $paypal_client)
	{
		$this->paypal_client = $paypal_client;
		$this->setup_fee["currency_code"] = $this->getCurrency();
	}

	public function getCurrency()
	{
		return $this->paypal_client->getCurrency();
	}

	public function addTrial(string $unit, int $count)
	{
		$billing_cycles[] = [
			"tenure_type" => "TRIAL",
			"sequence" => $this->cycle_count,
			"frequency" => [
			  "interval_unit" => $unit, # DAY | WEEK | MONTH | YEAR
			  "interval_count" => $count
			],
			"pricing_scheme" => [
			  "fixed_price" => [
			    "value" => 0,
			    "currency_code" => $this->getCurrency()
			  ]
			]
		];

		$this->cycle_count++;
		return $this;
	}

	public function addMonthlyPlan(int $price=0, int $total_cycles=1)
	{
		$billing_cycles[] = [
			"tenure_type" => "REGULAR",
			"sequence" => $this->cycle_count,
			"total_cycles" => $total_cycles,
			"frequency" => [
			  "interval_unit" => "MONTH", # DAY | WEEK | MONTH | YEAR
			  "interval_count" => 1
			],
			"pricing_scheme" => [
			  "fixed_price" => [
			    "value" => $price,
			    "currency_code" => $this->getCurrency()
			  ]
			]
		];

		$this->cycle_count++;
		return $this;
	}

	public function addYearlyPlan(int $price=0, int $total_cycles=1)
	{
		$billing_cycles[] = [
			"tenure_type" => "REGULAR",
			"sequence" => $this->cycle_count,
			"total_cycles" => $total_cycles,
			"frequency" => [
			  "interval_unit" => "YEAR", # DAY | WEEK | MONTH | YEAR
			  "interval_count" => 1
			],
			"pricing_scheme" => [
			  "fixed_price" => [
			    "value" => $price,
			    "currency_code" => $this->getCurrency()
			  ]
			]
		];

		$this->cycle_count++;
		return $this;
	}

	public function addDailyPlan(int $price=0, int $total_cycles=1)
	{
		$billing_cycles[] = [
			"tenure_type" => "REGULAR",
			"sequence" => $this->cycle_count,
			"total_cycles" => $total_cycles,
			"frequency" => [
			  "interval_unit" => "DAY", # DAY | WEEK | MONTH | YEAR
			  "interval_count" => 1
			],
			"pricing_scheme" => [
			  "fixed_price" => [
			    "value" => $price,
			    "currency_code" => $this->getCurrency()
			  ]
			]
		];

		$this->cycle_count++;
		return $this;
	}

	public function addWeeklyPlan(int $price=0, int $total_cycles=1)
	{
		$billing_cycles[] = [
			"tenure_type" => "REGULAR",
			"sequence" => $this->cycle_count,
			"total_cycles" => $total_cycles,
			"frequency" => [
			  "interval_unit" => "WEEK", # DAY | WEEK | MONTH | YEAR
			  "interval_count" => 1
			],
			"pricing_scheme" => [
			  "fixed_price" => [
			    "value" => $price,
			    "currency_code" => $this->getCurrency()
			  ]
			]
		];

		$this->cycle_count++;
		return $this;
	}

	public function addProduct(Array|int $config)
	{
		$this->product = new Product($config);
		return $this;
	}

	public function setBrandName(string $brand_name)
	{
		$this->application_context["brand_name"] = $brand_name;
		return $this;
	}

	public function addReturnAndCancelUrl(string $return_url, string $cancel_url)
	{
		$this->application_context["return_url"] = $return_url;
		$this->application_context["cancel_url"] = $cancel_url;

		return $this;
	}

	public function setupFee(int $fee=0)
	{
		$this->setup_fee = [
			"value" => $fee,
			"currency_code" => $this->getCurrency()
		];
		return $this;
	}

	public function setupSubscription(string $customer_name, string $email)
	{
		$this->subscriber["name"]["given_name"] = $customer_name;
		$this->subscriber["email_address"] = $email;

		// TODO: setup subscription
	}
}

namespace App\Packages\Gateways\PayPalClient;

use App\Packages\Gateways\PayPalClient;


interface Registerable
{
	public function setPayPalClient(PayPalClient $paypal_client);
	public function setup(PayPalClient $paypal_client): Registerable;
}


abstract class WrapperMixin
{
	private $_result = null;

	public function __construct()
	{
		$this->_result = new \stdClass();
	}

	public function __get($key)
	{
		if (isset($this->_result->$key))
			return $this->_result->$key;
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


class Product extends WrapperMixin implements Registerable
{
	private $paypal = null;
	private $_data = [
		"name"	=> "",
		"type"	=> "" // SERVICE | PHYSICAL | DIGITAL
	];

	public function __construct(Array|null $config = null)
	{
		parent::__construct();

		if (is_array($config) && $config != null)
			$this->_data = $config + $this->_data;
	}

	public function setPayPalClient(PayPalClient $paypal)
	{
		$this->paypal = $paypal;
	}

	public function setup(PayPalClient $paypal): Registerable
	{
		$req = $this->paypal->client->request("POST", "catalogs/products", ["json" => $this->_data]);
		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
		}
		return $this;
	}
}


class Plan extends WrapperMixin implements Registerable
{
	private $paypal = null;
	private $_data = [
	    "product_id" => "",
	    "name" => "",
	    "billing_cycles" => [
			[
			  "tenure_type" => "REGULAR",
			  "sequence" => 1,
			  "total_cycles" => 0,
			  "frequency" => [
			    "interval_unit" => "MONTH",
			    "interval_count" => 1
			  ],
			  "pricing_scheme" => [
			    "fixed_price" => [
			      "value" => 0,
			      "currency_code" => "USD"
			    ]
			  ]
			]
		],
		"payment_preferences" => [
			"auto_bill_outstanding" => true,
			"payment_failure_threshold" => 0
		]
	];

	public function __construct(Array|null $config = null)
	{
		if (is_array($config) && $config != null)
			$this->_data = $config + $this->_data;
	}

	public function setPayPalClient(PayPalClient $paypal)
	{
		$this->paypal = $paypal;
	}

	public function setup(PayPalClient $paypal): Registerable
	{
		$req = $this->paypal->client->request("POST", "billing/plans", ["json" => $this->_data]);
		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
		}
		return $this;
	}

	public function deactivate()
	{
		$req = $this->paypal->client->request("POST", "billing/plans/${$this->id}/deactivate");
		if ($req->getStatusCode() == 204)
		{
			$this->status = "INACTIVE";
			return true;
		}
		return false;
	}

	public function activate()
	{
		$req = $this->paypal->client->request("POST", "billing/plans/${$this->id}/activate");
		if ($req->getStatusCode() == 204)
		{
			$this->status = "ACTIVE";
			return true;
		}
		return false;
	}
}


class Subscription extends WrapperMixin implements Registerable
{
	private $paypal = null;
	private $_data = [
	    "plan_id" 		=> "",	# Required
	    "auto_renewal" 	=> false,
	    "application_context" => [
	        "shipping_preference" 	=> "NO_SHIPPING",
	        "payment_method" 		=> [
	            "payee_preferred" => "IMMEDIATE_PAYMENT_REQUIRED"
	        ],
	        "return_url" => "", # Required
	        "cancel_url" => ""  # Required
	    ]
	];

	public function __construct(Array|null $config = null)
	{
		if (is_array($config) && $config != null)
			$this->_data = $config + $this->_data;
	}

	public function setPayPalClient(PayPalClient $paypal)
	{
		$this->paypal = $paypal;
	}

	public function setup(PayPalClient $paypal): Registerable
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions", ["json" => $this->_data]);
		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
		}
		return $this;
	}

	public function addReturnAndCancelUrl(string $return_url, string $cancel_url)
	{
		$this->_data["application_context"]["return_url"] = $return_url;
		$this->_data["application_context"]["cancel_url"] = $cancel_url;
		return $this;
	}

	public function setNoShipping()
	{
		$this->_data["application_context"]["shipping_preference"] = "NO_SHIPPING";
		return $this;
	}

	public function setAutoRenewal(bool $auto_renewal = true)
	{
		$this->_data["auto_renewal"] = $auto_renewal;
		return $this;
	}

	public function setBrandName(string $brand_name)
	{
		$this->_data["application_context"]["brand_name"] = $brand_name;
		return $this;
	}

	public function setSubscriber(string $email, string $name=null)
	{
		if (!empty($name))
			$this->_data["subscriber"]["name"]["given_name"] = $name;
		$this->_data["subscriber"]["email_address"] = $email;
		return $this;
	}

	public function setPlanById(string $planId)
	{
		$this->_data["plan_id"] = $planId;
		return $this;
	}

	public function getSubscriptionLink()
	{
		return isset($this->links[0]) ? $this->links[0]->href : null;
	}

	public function cancel(string $reason="Subscription canceled")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/cancel", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "CANCELLED";
			return true;
		}
		return false;
	}

	public function suspend(string $reason="Item out of stock")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/suspend", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "SUSPENDED";
			return true;
		}
		return false;
	}

	public function activate(string $reason="Reactivating the subscription")
	{
		$req = $this->paypal->client->request("POST", "billing/subscriptions/{$this->id}/activate", ["json" => [
			"reason" => $reason
		]]);

		if ($req->getStatusCode() == 204)
		{
			$this->status = "ACTIVE";
			return true;
		}
		return false;
	}
}

/*
$paypal = new PayPalClient($config);
$paypal->listProducts();
$paypal->createProduct();
$paypal->getProduct($id);

$paypal->createPlan();
$paypal->getPlan($id);

$paypal->createSubscription();
$paypal->updateSubscription();
$paypal->getSubscription($id);


$paypal->addTrial()
	->addDailyPlan()
	->addMonthlyPlan()
	->addYearlyPlan()
	->addReturnAndCancelUrl()
	->setupSubscription()

$subscription = new Subscription($paypal, ...);
$subscription->cancel();
$subscription->suspend();
*/
