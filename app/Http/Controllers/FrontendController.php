<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class FrontendController extends Controller
{
    // for React Frontend
    public function index(Request $request)
    {
        return view("frontend");
    }
}