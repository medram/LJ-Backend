<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Packages\Gateways\PayPalClient;
use App\Packages\Gateways\PayPalClient\Product;
use App\Packages\Gateways\PayPalClient\Plan;
use App\Packages\Gateways\PayPalClient\Subscription;


class CheckoutController extends Controller
{
    public function getPayPalPlanId(Request $request, $id)
    {
        $config = [
            "sandbox" => true,
            "client_id" => "ATdHKLmzwFJVeaDIGMvitB1huxKIOItJ2grUUlFTQPpDPwPGfgywYs2-6gjD6ZNCU1GClGXYJIS9DCZ0",
            "secret"    => "EDxasUOxzlT4-yvM0DdZN8Hex7GaxNUZD0QigpLKTMJXq319CG8SQoSRoD0QdcvMeop1TXNkQrMg3KhN"
        ];

        $paypal = new PayPalClient($config);
        //$product = $paypal->register(new Product(["name" => "StreamAI", "type" => "SERVICE"]));
        $product_id = "PROD-4JP69483299854843";
        $plan_id = "P-4R752785904412153MRSRY4A";
        $subscription_id = "I-ESSRG40AR2M5";

        $subscription = $paypal->getSubscriptionById($subscription_id);
        $subscription->activate();
        $subscription = $paypal->getSubscriptionById($subscription_id);
        dd($subscription->getResult());

        /*$subscription = new Subscription();
        $subscription->setBrandName("AskPDF3")
                    ->setPlanById($plan_id)
                    ->setNoShipping()
                    //->setAutoRenewal()
                    ->addReturnAndCancelUrl("http://localhost:7000/thank-you", "http://localhost:3000/pricing")
                    ->setSubscriber("ali@gmail.com", "Ali");

        $paypal->register($subscription);

        dd($subscription->getSubscriptionLink());*/



        /*
        $plan = $paypal->register(new Plan([
            "product_id" => $product_id,
            "name" => "Extra Plan 2",
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
                      "value" => 14.99,
                      "currency_code" => "USD"
                    ]
                  ]
                ]
              ]
        ]));
        */


        // TODO:
        // 1. Create paypal product (or use the existed one)
        // 2. Create paypal plan (or use the existed one)
        // 3. returning Paypal plan ID

    }
}
