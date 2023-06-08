<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Rules\StripTagsRule;


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
        if ($user)
        {
            $user->api_token = null;
            $user->save();
        }
        /*
        return response()->json([
            'errors' => true,
            'message' => 'A valid User token required!'
        ], 403);*/

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
        $request->validate([
            "username"  => ["required", "string", "min:4", new StripTagsRule],
            "email" => ["required", "email"]
        ]);

        $fields = $request->all();
        $user = $request->user();
        $user->username = $fields['username'];
        $user->email = $fields['email'];

        if ($user->save())
        {
            return response()->json([
                "errors" => false,
                "user"  => $user,
                "message" => "Updated successfully."
            ]);
        }

        return response()->json([
            "errors" => true,
            "message" => "Something went wrong!"
        ]);
    }

    public function subscription(Request $request)
    {
        $user = $request->user();

        //$subscription = $user->subscription()->first();
        $subscription = Subscription::select("subscriptions.*", "plans.name as plan_name", "plans.price", "plans.billing_cycle", "plans.is_free")
                    ->leftJoin("plans", "subscriptions.plan_id", "=", "plans.id")
                    ->where("user_id", $user->id)
                    ->first();

        return response()->json([
            "errors" => false,
            "subscription" => $subscription
        ]);
    }


    public function invoices(Request $request)
    {
        $user = $request->user();

        $invoices = Invoice::select("invoices.*", "plans.name as plan_name", "plans.price", "plans.billing_cycle", "plans.is_free")
                    ->leftJoin("plans", "invoices.plan_id", "=", "plans.id")
                    ->where("user_id", $user->id)
                    ->get();

        return response()->json([
            "errors" => false,
            "invoices" => $invoices
        ]);
    }


    public function register(Request $request)
    {
        $request->validate([
            "username"  => ["required", "min:4", "max:25", new StripTagsRule],
            "email"     => "required|email|unique:users",
            "password"  => "required|min:6|max:40"
        ]);

        $data = $request->all();
        $data['name'] = $data['username'];
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // TODO: Send email verification.

        return response()->json([
            "error" => false,
            "message" => "Registered Successfully!"
        ], 201);
    }

    public function currentUser(Request $request)
    {
        $currentUser = $request->user();
        if ($currentUser)
        {
            return response()->json([
                "errors" => false,
                "user" => $currentUser
            ]);
        }

        return response()->json([
            "errors" => true,
            "message" => "Not User Found!"
        ], 404);
    }

}
