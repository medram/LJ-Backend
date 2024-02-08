<?php

return [

	"paypal" => [
		"WEBHOOK_URL" => isset($_SERVER['HTTP_HOST']) ? "https://{$_SERVER['HTTP_HOST']}/api/v1/webhook/paypal" : "",
		"WEBHOOK_EVENTS" => [
		    "PAYMENT.SALE.COMPLETED",
		    "BILLING.SUBSCRIPTION.ACTIVATED",
		    "BILLING.SUBSCRIPTION.CANCELLED",
		    "BILLING.SUBSCRIPTION.SUSPENDED",
		    "BILLING.SUBSCRIPTION.EXPIRED",
		    "BILLING.SUBSCRIPTION.PAYMENT.FAILED",
		],
	],

	"stripe" => [
		"WEBHOOK_URL" => isset($_SERVER['HTTP_HOST']) ? "https://{$_SERVER['HTTP_HOST']}/api/v1/webhook/stripe" : "",
		"WEBHOOK_EVENTS" => [
			"invoice.paid",
			"customer.subscription.updated",
			"customer.subscription.deleted",
			"customer.subscription.trial_will_end",
			"invoice.payment_failed",
		]
	]
];
