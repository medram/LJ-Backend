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
            ["name" => "CHAT_AGENT_MODEL",          "value" => "gpt-3.5-turbo-1106",    "type" => "string"],
            ["name" => "CHAT_AGENT_MODEL_TEMP",     "value" => "0.5",                   "type" => "float"],
            ["name" => "CHAT_TOOLS_MODEL",  "value" => "gpt-3.5-turbo-16k",     "type" => "string"],
            ["name" => "CHAT_TOOLS_MODEL_TEMP",  "value" => "0.3",              "type" => "float"],
        ];

        // Insert only the new keys
        $settings = getAllSettings();
        $settingsKeys = array_keys($settings);

        $newSettingsToInsert = array_filter($data, fn($option) => !in_array($option["name"], $settingsKeys) );

        // Insert new settings
        Setting::insert($newSettingsToInsert);
    }
}
