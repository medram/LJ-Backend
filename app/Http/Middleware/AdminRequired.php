<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\AccessToken;
use App\Packages\LC\LCManager;
use Carbon\Carbon;
use Auth;

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
        $accessToken = AccessToken::where("token", hash("sha256", $token))->first();

        if ($accessToken && ($accessToken->expires_at == null || Carbon::now()->lt($accessToken->expires_at))) {
            $user = $accessToken->user;

            if ($user && $user->is_active && $user->isAdmin()) {
                $lcManager = LCManager::getInstance();

                if (!$lcManager->check()) {
                    return response()->json([
                        'error' => true,
                        'message' => base64_decode("SW52YWxpZCBMaWNlbnNlIENvZGUK")
                    ], 403);
                }

                # Set the user
                Auth::login($user);

                return $next($request);
            }
        } else {
            // delete the expired access token
            if ($accessToken) {
                $accessToken->delete();
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'Login is required'
        ], 401);
    }
}
