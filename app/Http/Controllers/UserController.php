<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;



class UserController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (Auth::attempt($credentials, function (User $user) {
            return $user->is_active;
        }))
        {
            $token = Str::random(60);
            $user = $request->user();
            $user->api_token = hash('sha256', $token);
            $user->save();

            return response()->json([
                'errors'   => false,
                'token'     => $token,
                'message'   => 'Logged in successfully.',
                'user'      => $user
            ]);
        }

        return response()->json([
            'errors' => true,
            'message' => 'Incorrect email or password!',
        ]);
    }

    public function logout(Request $request)
    {
        $token = userToken($request);
        $user = User::where('api_token', hash('sha256', $token))->first();
        if (!$user)
        {
            return response()->json([
                'errors' => true,
                'message' => 'A valid User token required!'
            ], 403);
        }

        $user->api_token = null;
        $user->save();

        // Used for sessions (Not required).
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'errors' => false,
            'message' => 'Logged out successfully.'
        ]);
    }

    public function dashboard(Request $request)
    {
        return response()->json([
            "dashboard_data" => $request->user()
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            "error" => false,
            "user" => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $fields = $request->validate([
            "username"  => ["required", "string", "min:4"],
            "email" => ["required", "email"]
        ]);

        $user = $request->user();
        $user->username = $fields['username'];
        $user->email = $fields['email'];

        if ($user->save())
        {
            return response()->json([
                "error" => false,
                "user"  => $user
            ]);
        }

        return response()->json([
            "error" => true,
            "message" => "Something went wrong!"
        ]);
    }

    public function gallery(Request $request)
    {
        $user = $request->user();
        //$images = Gallery::where('user_id', $user->id)->get();

        $images = $user->images()->get();

        return response()->json([
            "error" => false,
            "images" => $images
        ]);
    }

    public function subscription(Request $request)
    {
        $user = $request->user();

        $subscription = $user->subscription()->first();

        return response()->json([
            "error" => false,
            "subscription" => $subscription
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            "username"  => "required|min:4|max:25",
            "email"     => "required|email|unique:users",
            "password"  => "required|min:6|max:40"
        ]);

        $data['name'] = $data['username'];
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // TODO: Send email verification.

        return response()->json([
            "error" => false,
            "message" => "Registered Successfully!"
        ], 201);
    }

}
