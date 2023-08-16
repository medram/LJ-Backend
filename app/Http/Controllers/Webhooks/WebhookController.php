<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;


abstract class WebhookController extends Controller
{
	abstract public function handle(Request $request);
}
