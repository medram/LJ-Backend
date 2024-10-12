<?php

namespace App\Http\Controllers\GatewaySynchronizers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

abstract class BaseGatewaySynchronizer extends Controller
{
    abstract public function sync(Request $request);
}
