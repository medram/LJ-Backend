<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected static $all_settings = [];

    public $timestamps = false;

    public static $ACCEPT_HTML = [
        "HEAD_CODE",
    ];

    public static function getSetting($key)
    {
        if (isset(Setting::$all_settings[$key]))
            return Setting::$all_settings[$key];

        // refresh settings then get the value
        //return $this::where('name', $key)->first();
        Setting::getAllSettings();

        return isset(Setting::$all_settings[$key])? Setting::$all_settings[$key] : null;
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

    # Clear the values and it will be refresh later automatically.
    public static function clear()
    {
        Setting::$all_settings = [];
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

    public static function filterInput($name, $value, $type="string")
    {

        if (in_array($name, self::$ACCEPT_HTML))
            return $value;

        if ($type === "bool" or $type === "boolean")
            return $value == 0 ? 0 : 1;

        if ($type === "int" or $type === "integer")
            return intval($value);

        if ($type === "float")
            return (float)$value;

        return strip_tags($value);
    }

    public static function filterInputs($fields)
    {
        $types = self::getTypes();
        $filtered_fields = [];

        foreach ($fields as $name => $value)
        {
            $type = $types[$name];
            $filtered_fields[$name] = self::filterInput($name, $value, $type);
        }

        return $filtered_fields;
    }

    public static function getTypes()
    {
        $all_settings = Setting::all();
        $types = [];
        foreach($all_settings as $setting)
        {
            $types[$setting->name] = $setting->type;
        }
        return $types;
    }
}
