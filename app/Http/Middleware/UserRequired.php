<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserRequired
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

        if (!$user)
        {
            return response()->json([
                'error' => true,
                'message' => 'Login is required'
            ], 401);
        }

        # Set the user
        Auth::login($user);

        return $next($request);
    }
}
