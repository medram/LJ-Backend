<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;
use Auth;
use App\Packages\LC\LCManager;


// Admin Required Middleware
class AdminRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim(str_ireplace("Bearer ", "", $request->header('Authorization')));
        $user = User::where('api_token', hash('sha256', $token))->first();
        $lcManager = LCManager::getInstance();

        if (!$lcManager->check())
        {
            return response()->json([
                'error' => true,
                'message' => base64_decode("SW52YWxpZCBMaWNlbnNlIENvZGUK")
            ], 403);
        }

        if (!$user or !$user->isAdmin())
        {
            return response()->json([
                'error' => true,
                'message' => 'Admin login required'
            ], 401);
        }

        # Set the user
        Auth::login($user);

        return $next($request);
    }
}
