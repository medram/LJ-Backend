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
            Setting::$all_settings[$setting->name] = $setting->value;
        }

        return Setting::$all_settings;
    }
}
