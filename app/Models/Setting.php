<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected static $all_settings = [];

    public $timestamps = false;


    public function getSetting($key)
    {
        if (isset($all_settings[$key]))
            return $all_settings[$key];

        // refresh settings then get the value
        //return $this::where('name', $key)->first();
        Setting::getAllSettings();

        return isset($all_settings[$key])? $all_settings[$key] : null;
    }

    public static function getAllSettings()
    {
        // reformat it as a JSON.
        foreach(Setting::all() as $setting)
        {
            $value = self::parse_value($setting);

            Setting::$all_settings[$setting->name] = $value;
        }

        return Setting::$all_settings;
    }

    public static function parse_value($setting)
    {
        // avoid returning null values.
        $value = $setting->value === null ? "" : $setting->value;

        if ($setting->type === "boolean")
            $value = ($value == "0" or $value == "false")? false : true;
        else if ($setting->type === "int" or $setting->type === "integer")
            $value = (int)$value;
        else if ($setting->type === "float")
            $value = (float)$value;

        return $value;
    }
}
