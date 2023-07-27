<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;


class SettingsController extends Controller
{
    // Exclude this list of sensitive data from public settings
    public $private_settings = [
        //e.g. "SITE_NAME",
        "SMTP_HOST",
        "SMTP_PORT",
        "SMTP_USER",
        "SMTP_PASSWORD",
        "SMTP_MAIL_ENCRIPTION",
        "SMTP_ALLOW_INSECURE_MODE",
        "PM_PAYPAL_CLIENT_ID",
        "PM_PAYPAL_CLIENT_SECRET",
        "PM_STRIP_PUBLIC_KEY",
        "PM_STRIP_PRIVATE_KEY",
        "RAPID_API_KEY",
        "RAPID_API_HOST",
        "OPENAI_API_KEY",
        "PM_PAYPAL_WEBHOOK_ID",
        "LICENSE_CODE",
    ];

    // Get public website settings
    public function publicSettings(Request $request)
    {
        $settings = getAllSettings();
        $filtered_settings = [];

        // TODO: filters out private settings
        foreach ($settings as $name => $value)
        {
            if (!in_array($name, $this->private_settings))
                $filtered_settings[$name] = $value;
        }

        return response()->json([
            "error" => false,
            "settings" => $filtered_settings
        ]);
    }

    // List all available settings
    public function list(Request $request)
    {
        $settings = getAllSettings();

        return response()->json([
            'errors' => false,
            'settings' => $settings
        ]);
    }

    // Update settings
    public function update(Request $request)
    {
        $fields = $request->json()->all();
        // Filter input fields against XSS attacks.
        $fields = Setting::filterInputs($fields);

        try
        {
            foreach ($fields as $key => $value)
            {
                $option = Setting::where('name', $key)->get()->first();
                if ($option)
                {
                    $option->value = $value === null? "" : $value;
                    $option->save();
                }
            }
        } catch (\Exception $e){
            return response()->json([
                'errors' => true,
                'message' => "Something went wrong!"
            ]);
        }

        return response()->json([
            'errors' => false,
            'message' => "Updated successfully."
        ]);
    }
}
