<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;


class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $override_options = [
            "CHAT_AVAILABLE_PLUGINS",
        ];

        $data = [
            ["name" => "SITE_NAME",         "value" => "ChatPDF",       "type" => "string"],
            ["name" => "SITE_DESC",         "value" => "",              "type" => "string"],
            ["name" => "SITE_KEYWORDS",     "value" => "",              "type" => "string"],
            ["name" => "TIMEZONE",          "value" => "UTC",           "type" => "string"],
            ["name" => "CURRENCY",          "value" => "USD",           "type" => "string"],
            ["name" => "CURRENCY_SYMBOL",   "value" => "$",             "type" => "string"],
            ["name" => "CURRENCY_POSITION", "value" => "LEFT",          "type" => "string"],
            ["name" => "HEAD_CODE",         "value" => "",              "type" => "string"],
            ["name" => "SITE_LOGO",         "value" => "",              "type" => "string"],
            ["name" => "SITE_FAVICON",      "value" => "logo192.png",   "type" => "string"],
            ["name" => "SHOW_LOGO",         "value" => "0",             "type" => "boolean"],
            ["name" => "SMTP_FROM",         "value" => "",              "type" => "string"],
            ["name" => "SMTP_HOST",         "value" => "",              "type" => "string"],
            ["name" => "SMTP_PORT",         "value" => "465",           "type" => "int"],
            ["name" => "SMTP_USER",         "value" => "",              "type" => "string"],
            ["name" => "SMTP_PASSWORD",     "value" => "",              "type" => "string"],
            ["name" => "SMTP_MAIL_ENCRIPTION",                  "value" => "ssl",           "type" => "string"],
            ["name" => "SMTP_ALLOW_INSECURE_MODE",              "value" => "1",             "type" => "boolean"],
            ["name" => "EMAIL_TEMPLATE_REGISTRATION",           "value" => "",              "type" => "string"],
            ["name" => "EMAIL_TEMPLATE_PASSWORD_RESET",         "value" => "",              "type" => "string"],
            ["name" => "EMAIL_TEMPLATE_SUBSCRIPTION_SUCCESSFULL", "value" => "",            "type" => "string"],
            ["name" => "EMAIL_TEMPLATE_SUBSCRIBTION_EXPIRED",   "value" => "",              "type" => "string"],
            ["name" => "EMAIL_TEMPLATE_SUBSCRIPTION_RENEWED",   "value" => "",              "type" => "string"],
            ["name" => "PM_PAYPAL_CLIENT_ID",   "value" => "",          "type" => "string"],
            ["name" => "PM_PAYPAL_CLIENT_SECRET", "value" => "",          "type" => "string"],
            ["name" => "PM_PAYPAL_SANDBOX",     "value" => "1",         "type" => "boolean"],
            ["name" => "PM_PAYPAL_STATUS",      "value" => "0",         "type" => "boolean"],
            ["name" => "PM_PAYPAL_WEBHOOK_ID",  "value" => "",          "type" => "string"],
            ["name" => "PM_PAYPAL_PRODUCT_ID",  "value" => "",          "type" => "string"],
            ["name" => "PM_STRIP_PUBLIC_KEY",   "value" => "",          "type" => "string"],
            ["name" => "PM_STRIP_SECRET_KEY",  "value" => "",          "type" => "string"],
            ["name" => "PM_STRIP_SECRET_KEY_TEST",  "value" => "",          "type" => "string"],
            ["name" => "PM_STRIP_SANDBOX",      "value" => "1",         "type" => "boolean"],
            ["name" => "PM_STRIP_STATUS",       "value" => "0",         "type" => "boolean"],
            ["name" => "PM_STRIP_PRODUCT_ID",   "value" => "",          "type" => "string"],
            ["name" => "PM_STRIP_WEBHOOK_ID",   "value" => "",          "type" => "string"],
            ["name" => "RAPID_API_KEY",         "value" => "",          "type" => "string"],
            ["name" => "RAPID_API_HOST",        "value" => "askpdf1.p.rapidapi.com", "type" => "string"],
            ["name" => "OPENAI_API_KEY",        "value" => "",          "type" => "string"],
            ["name" => "LICENSE_CODE",          "value" => "",          "type" => "string"],
            ["name" => "TRIAL_PLANS",           "value" => "0",         "type" => "int"],
            ["name" => "TRIAL_DAYS",            "value" => "0",        "type" => "int"],
            ["name" => "CHAT_AGENT_MODEL",          "value" => "gpt-3.5-turbo-16k",     "type" => "string"],
            ["name" => "CHAT_AGENT_MODEL_TEMP",     "value" => "0.5",                   "type" => "float"],
            ["name" => "CHAT_TOOLS_MODEL",          "value" => "gpt-3.5-turbo-16k",     "type" => "string"],
            ["name" => "CHAT_TOOLS_MODEL_TEMP",     "value" => "0.5",                   "type" => "float"],
            ["name" => "CHAT_PLANNER_AGENT_MODEL",  "value" => "gpt-3.5-turbo",         "type" => "string"],
            ["name" => "CHAT_PLANNER_AGENT_MODEL_TEMP",  "value" => "0",                "type" => "float"],

            ["name" => "CHAT_AVAILABLE_PLUGINS",    "value" => $this->get_available_plugins(), "type" => "string"],
            ["name" => "SELECTED_PLUGINS",          "value" => "[]", "type" => "string"],
        ];

        // Delete the options that need to be updated
        foreach ($override_options as $value)
        {
            Setting::where("name", $value)->delete();
        }

        // Insert only the new keys
        $settings = getAllSettings();
        $settingsKeys = array_keys($settings);

        // Delete the keys
        foreach ($override_options as $value)
        {
            if (($key = array_search($value, $settingsKeys)) !== false) {
                unset($settingsKeys[$key]);
            }
        }

        $newSettingsToInsert = array_filter($data, fn($option) => !in_array($option["name"], $settingsKeys) );

        // Insert new settings
        Setting::insert($newSettingsToInsert);
    }

    public function get_available_plugins()
    {
        return json_encode([
            [
                "name" => "DocumentPlugin",
                "desc" => "Useful to look up information from documents.",
                "beta" => true,
            ],
            [
                "name" => "DocumentSummarizationPlugin",
                "desc" => "Useful to summarize documents.",
                "beta" => true,
            ],
            [
                "name" => "SimpleCalculatorPlugin",
                "desc" => "Useful to perform numerical calculations correctly.",
                "beta" => false,
            ],
            [
                "name" => "LinePlotPlugin",
                "desc" => "Useful to plot line graphs.",
                "beta" => true,
            ],
            [
                "name" => "BarPlotPlugin",
                "desc" => "Useful to plot bar graphs.",
                "beta" => true,
            ],
            [
                "name" => "PiePlotPlugin",
                "desc" => "Useful to plot pie graphs.",
                "beta" => true,
            ],
            [
                "name" => "CurrentDatetimePlugin",
                "desc" => "Useful to look up current date, time, year, ...etc",
                "beta" => false,
            ],
        ], JSON_UNESCAPED_SLASHES);
    }
}
