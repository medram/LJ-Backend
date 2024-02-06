<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Plan;
use App\Models\Page;
use App\Rules\StripTagsRule;
use App\Packages\LC\LCManager;

use Mail;

$settings = getAllSettings();


class CommonController extends Controller
{
    // Get all active plans
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

    // Get all available payment methods
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
                "sandbox"   => getSetting("PM_STRIP_SANDBOX"),
            ];
        }

        return response()->json([
            "errors" => false,
            "payment_methods" => $payment_mothods
        ]);
    }

    // send email to the website owner
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

    // Get all available website pages
    public function getPages(Request $request)
    {
        # Get all active pages
        $pages = Page::where("status", 1)->get();

        # Don't return page contents
        foreach($pages as $k => $page)
        {
            $pages[$k]->content = "";
        }

        return response()->json([
            "errors" => false,
            "pages"  => $pages ? $pages : []
        ]);
    }

    // Get website page details
    public function getPage(Request $request, string $slug)
    {
        # Get all active pages
        $page = Page::where([
            "status" => 1,
            "slug" => $slug
        ])->first();

        if ($page)
        {
            return response()->json([
                "errors" => false,
                "page"  => $page
            ]);
        }

        return response()->json([
            "errors" => false,
            "message"  => "Page not found"
        ], 404);
    }

    // Send a test email (useful for SMTP configuration)
    public function sendTestEmail(Request $request)
    {
        $fields = $request->validate([
            "email" => "email|required"
        ]);

        $email = $fields["email"];

        try {
            Mail::raw("This is just a test email message :D, that's great, it seems working ;D", function ($message) use($email) {
                $message->to($email);
                //$message->replyTo($settings['SMTP_USER'], $settings['SITE_NAME']);
                $message->subject("Test email message");
            });

            return response()->json([
                "errors" => false,
                "message" => "Sent successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "errors" => true,
                "message" => "Couldn't send the email, please check your SMTP settings again!"
            ]);
        }
    }

    public function LC(Request $request)
    {
        $lcManager = LCManager::getInstance();

        if ($lcManager->check())
        {
            return response()->json([
                'errors' => false,
                'message' => "LCD"
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => "LCE"
        ]);
    }

    // Inform frontend with demo status
    public function demo(Request $request)
    {
        if (isDemo())
        {
            return response()->json([
                "errors" => false,
                "status" => true
            ]);
        }

        return response()->json([
            "errors" => false,
            "status" => false
        ]);
    }

    public function LCInfo(Request $request)
    {
        $lcManager = LCManager::getInstance();
        $info = $lcManager->LCInfo();

        if ($info)
        {
            return response()->json([
                'errors' => false,
                'info' => base64_encode(base64_encode(json_encode($info)))
            ]);
        }

        return response()->json([
            'errors' => true,
            'info' => ""
        ]);
    }
}
