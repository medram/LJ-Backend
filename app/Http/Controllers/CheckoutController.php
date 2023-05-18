<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Packages\Gateways\PayPal\PayPalClient;
use App\Packages\Gateways\PayPal\Product;
use App\Packages\Gateways\PayPal\Plan;
use App\Packages\Gateways\PayPal\Subscription;


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

        /*$subscription = $paypal->getSubscriptionById($subscription_id);
        $subscription->activate();
        $subscription = $paypal->getSubscriptionById($subscription_id);
        dd($subscription->getResult());*/

        $plan = new Plan();
        $plan->setPayPalClient($paypal)
            ->setName("SuperMan Plan")
            ->setProductById($product_id)
            ->addTrial('DAY', 7)
            ->addMonthlyPlan(11.99, 0)
            ->setup(); # required to register it to PayPal.

        //dd($plan->showData());

        $subscription = new Subscription();
        $subscription->setPayPalClient($paypal)
                    ->setBrandName("AskPDF3")
                    ->setPlanById($plan->id)
                    ->setNoShipping()
                    //->setAutoRenewal()
                    ->addReturnAndCancelUrl("http://localhost:7000/thank-you", "http://localhost:3000/pricing")
                    ->setSubscriber("ali@gmail.com", "Ali")
                    ->setup();


        dd($subscription->getSubscriptionLink());



        // TODO:
        // 1. Create paypal product (or use the existed one)
        // 2. Create paypal plan (or use the existed one)
        // 3. returning Paypal plan ID

    }
}
