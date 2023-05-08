<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;


class SettingsController extends Controller
{
    public function list(Request $request)
    {
        $settings = getAllSettings();

        return response()->json([
            'errors' => false,
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $fields = $request->json()->all();

        try
        {
            foreach ($fields as $key => $value)
            {
                $option = Setting::where('name', $key)->get()->first();
                if ($option)
                {
                    $option->value = $value;
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
