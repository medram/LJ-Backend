<?php

use App\Models\Setting;


function getAllSettings()
{
	return Setting::getAllSettings();
}

function getSetting($key)
{
	return Setting::getSetting($key);
}


function userToken($request)
{
	return trim(str_ireplace("Bearer ", "", $request->header('Authorization')));
}
