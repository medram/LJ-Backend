<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class FrontendController extends Controller
{
    // Integrate React Frontend
    public function index(Request $request)
    {
        $settings = getAllSettings();

        return view("frontend", $settings);
    }
}
