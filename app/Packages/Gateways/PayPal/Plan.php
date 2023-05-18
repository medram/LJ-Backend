<?php
namespace App\Packages\Gateways\PayPal;

use App\Packages\Gateways\PayPal\WrapperMixin;
use App\Packages\Gateways\PayPal\PayPalClient;


class Plan extends WrapperMixin
{
	private $paypal = null;
	private $_sequence = 1;
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
		# To clear data.
		$this->_data["billing_cycles"] = [];
	}

	public function setPayPalClient(PayPalClient $paypal)
	{
		$this->paypal = $paypal;
		return $this;
	}

	public function setProductById(string $id)
	{
		$this->_data["product_id"] = $id;
		return $this;
	}

	public function setName(string $name)
	{
		$this->_data["name"] = $name;
		return $this;
	}

	public function addTrial(string $unit, int $count = 1)
	{
		$this->_data["billing_cycles"][] = [
			"tenure_type" => "TRIAL",
			"sequence" => $this->_sequence,
			"frequency" => [
				"interval_unit" => $unit, # DAY | WEEK | MONTH | YEAR
				"interval_count" => $count
			],
			"pricing_scheme" => [
				"fixed_price" => [
				 	"value" => 0,
				 	"currency_code" => $this->paypal->getCurrency()
				]
			]
		];

		$this->_sequence++;
		return $this;
	}

	public function addDailyPlan(float $price, int $total_cycles = 1)
	{
		$this->_data["billing_cycles"][] = [
			  "tenure_type" => "REGULAR",
			  "sequence" => $this->_sequence,
			  "total_cycles" => $total_cycles, # 0 = unlimited
			  "frequency" => [
			    "interval_unit" => "DAY", # DAY | WEEK | MONTH | YEAR
			    "interval_count" => 1
			  ],
			  "pricing_scheme" => [
			    "fixed_price" => [
			      "value" => $price,
			      "currency_code" => $this->paypal->getCurrency()
			    ]
			  ]
		];

		$this->_sequence++;
		return $this;
	}

	public function addWeeklyPlan(float $price, int $total_cycles = 1)
	{
		$this->_data["billing_cycles"][] = [
			  "tenure_type" => "REGULAR",
			  "sequence" => $this->_sequence,
			  "total_cycles" => $total_cycles, # 0 = unlimited
			  "frequency" => [
			    "interval_unit" => "WEEK", # DAY | WEEK | MONTH | YEAR
			    "interval_count" => 1
			  ],
			  "pricing_scheme" => [
			    "fixed_price" => [
			      "value" => $price,
			      "currency_code" => $this->paypal->getCurrency()
			    ]
			  ]
		];

		$this->_sequence++;
		return $this;
	}

	public function addMonthlyPlan(float $price, int $total_cycles = 1)
	{
		$this->_data["billing_cycles"][] = [
			  "tenure_type" => "REGULAR",
			  "sequence" => $this->_sequence,
			  "total_cycles" => $total_cycles, # 0 = unlimited
			  "frequency" => [
			    "interval_unit" => "MONTH", # DAY | WEEK | MONTH | YEAR
			    "interval_count" => 1
			  ],
			  "pricing_scheme" => [
			    "fixed_price" => [
			      "value" => $price,
			      "currency_code" => $this->paypal->getCurrency()
			    ]
			  ]
		];

		$this->_sequence++;
		return $this;
	}

	public function addYearlyPlan(float $price, int $total_cycles = 1)
	{
		$this->_data["billing_cycles"][] = [
			  "tenure_type" => "REGULAR",
			  "sequence" => $this->_sequence,
			  "total_cycles" => $total_cycles, # 0 = unlimited
			  "frequency" => [
			    "interval_unit" => "YEAR", # DAY | WEEK | MONTH | YEAR
			    "interval_count" => 1
			  ],
			  "pricing_scheme" => [
			    "fixed_price" => [
			      "value" => $price,
			      "currency_code" => $this->paypal->getCurrency()
			    ]
			  ]
		];

		$this->_sequence++;
		return $this;
	}

	public function paymentFailureThreshold(int $threshold = 0)
	{
		$this->_data["payment_preferences"]["payment_failure_threshold"] = $threshold;
		return $this;
	}

	public function setup()
	{
		$req = $this->paypal->client->request("POST", "billing/plans", ["json" => $this->_data]);
		if ($req->getStatusCode() == 201)
		{
			$this->setResult((string)$req->getBody());
		}
		return $this;
	}

	public function showData()
	{
		return $this->_data;
	}

	public function deactivate()
	{
		$req = $this->paypal->client->request("POST", "billing/plans/{$this->id}/deactivate");
		if ($req->getStatusCode() == 204)
		{
			$this->status = "INACTIVE";
			return true;
		}
		return false;
	}

	public function activate()
	{
		$req = $this->paypal->client->request("POST", "billing/plans/{$this->id}/activate");
		if ($req->getStatusCode() == 204)
		{
			$this->status = "ACTIVE";
			return true;
		}
		return false;
	}

	public function updatePrice(float $price)
	{
		$data = [
			"pricing_scheme" => [
				"billing_cycle_sequence" => 1, # foreach MONTH | YEAR | ...etc
				"pricing_scheme" => [
					"fixed_price" => [
						"value" => $price,
						"currency_code" => $this->paypal->getCurrency()
					]
				]
			]
		];

		$req = $this->paypal->client->request("POST", "billing/plans/{$this->id}/update-pricing-schemes", ["json" => $data]);

		if ($req->getStatusCode() == 204)
		{
			$this->refresh();
			return true;
		}
		return false;
	}

	public function refresh()
	{
		$req = $this->paypal->client->request("GET", "billing/plans/{$this->id}");
		if ($req->getStatusCode() == 200)
		{
			$this->setResult($req->getBody());
		}
		return $this;
	}
}
