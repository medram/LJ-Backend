<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;
use App\Rules\StripTagsRule;

use Mail;

$settings = getAllSettings();


class CommonController extends Controller
{
    public function plans(Request $request)
    {
        $plans = Plan::where([
            'status' => 1,
            'soft_delete' => 0
        ])->get();

        return response()->json([
            'error' => false,
            'plans' => $plans
        ]);
    }

    public function paymentMethods(Request $request)
    {
        $payment_mothods = [];

        if (getSetting("PM_PAYPAL_STATUS") == true)
        {
            $payment_mothods[] = [
                "name"      => "PayPal",
                "type"      => "PAYPAL",
                "key"       => getSetting("PM_PAYPAL_CLIENT_ID"),
                "sandbox"   => getSetting("PM_PAYPAL_SANDBOX"),
            ];
        }

        if (getSetting("PM_STRIP_STATUS") == true)
        {
            $payment_mothods[] = [
                "name"      => "Stripe",
                "type"      => "STRIPE",
                "key"       => getSetting("PM_STRIP_PUBLIC_KEY"),
                "sandbox"   => getSetting("PM_STRIP_SANDBOX"),
            ];
        }

        return response()->json([
            "errors" => false,
            "payment_methods" => $payment_mothods
        ]);
    }

    public function contactUs(Request $request)
    {
        $request->validate([
            "email"     => "required|email",
            "subject"   => ["required", "string", "min:6", "max:60", new StripTagsRule],
            "message"   => ["required", "string", "min:20", "max:512", new StripTagsRule]
        ]);

        $data = (object)$request->all();
        $settings = getAllSettings();

        try {
            // Send email address
            Mail::raw($data->message, function ($message) use ($settings, $data) {

                $message->to($settings['SMTP_USER'], $settings['SITE_NAME']);
                $message->replyTo($data->email);
                $message->subject("Contact Us - [{$data->email}]: {$data->subject}.");
            });
        } catch (\Exception $e) {
            echo $e;
            return response()->json([
                "errors" => true,
                "message" => "Something went wrong, please try again or later!"
            ]);
        }

        return response()->json([
            "errors" => false,
            "message" => "Sent Successfully."
        ]);
    }
}
