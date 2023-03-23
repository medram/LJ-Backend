<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;


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

        if (!$user or !$user->isAdmin())
        {
            return response()->json([
                'error' => true,
                'message' => 'Admin login required'
            ], 401);
        }


        return $next($request);
    }
}
