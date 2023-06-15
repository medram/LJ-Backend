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

use DB;


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

        // Send email verification.
        if ($user)
        {
            try {
                $user->sendVerificationEmail();
            } catch (\Exception $e){
                return response()->json([
                    "errors" => true,
                    "message" => "Something went wrong!, check out your SMTP config."
                ]);
            }
        }


        return response()->json([
            "error" => false,
            "message" => "Registered Successfully, please check you email inbox for account activation."
        ], 201);
    }

    public function verifyAccount(Request $request, string $token)
    {
        $personal_token = DB::table("personal_access_tokens")->where('token', $token)
                                                            ->where("expires_at", ">",  now())
                                                            ->first();

        if ($personal_token)
        {
            // activate user account
            $user = User::where('id', $personal_token->tokenable_id)->first();
            if ($user)
            {
                $user->is_active = 1;
                $user->email_verified_at = now();
                $user->save();
            }
            // delete the token
            DB::table("personal_access_tokens")->where('token', $token)->delete();
        }

        return redirect("/login");
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


    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            "current_password"  => "string|required",
            "new_password"      => "string|required|min:8"
        ]);

        $old_hashed_password = $user->password;
        $new_password = $request->json('new_password');
        $current_password = $request->json('current_password');

        if (!Hash::check($current_password, $old_hashed_password))
        {
            return response()->json([
                "errors" => true,
                "message" => "Incorrect Current password!"
            ], 200);
        }
        else
        {
            # Update user password
            $user->fill([
                "password" => Hash::make($new_password)
            ])->save();

            return response()->json([
                "errors" => false,
                "message" => "Password updated successfully."
            ], 200);
        }


        return response()->json([
            "errors" => true,
            "message" => "Something went wrong!"
        ], 200);
    }

    public function forgetPassword(Request $request)
    {
        // Get the user, check the email, send reset link via email
        $request->validate([
            "email" => "required|email"
        ]);

        $email = $request->json('email');
        $user = User::where('email', $email)->first();

        if ($user)
        {
            try {
                $user->sendResetPasswordEmail();
            } catch (\Exception $e){
                return response()->json([
                    "errors" => true,
                    "message" => "Something went wrong!, check out your SMTP config."
                ]);
            }
        }

        return response()->json([
            "errors" => false,
            "message" => "An email message has been sent, please check your email inbox!"
        ]);
    }

    public function resetPassword(Request $request)
    {
        /*
            request should be like:
            {
                "token": "...",
                "new_password": "...",
                "new_password_confirmation": "..."
            }
        */

        $request->validate([
            "token" => "required",
            "new_password" => "string|required|min:8|max:40|confirmed",
        ]);

        $token = $request->json('token');
        $new_password = $request->json('new_password');

        $tokenData = DB::table('password_reset_tokens')
            ->where('token', $token)->first();

        if ($tokenData)
        {
            // Save the new password and remove the password from "password_rest_tokens" table
            $user = User::where([
                "email" => $tokenData->email
            ])->first();

            if ($user)
            {
                $user->password = Hash::make($new_password);
                $user->save();

                //Delete the token
                DB::table('password_reset_tokens')->where('email', $user->email)
                ->delete();

                return response()->json([
                    "errors" => false,
                    "message" => "Updated successfully."
                ]);
            }
        }

        return response()->json([
            "errors" => true,
            "message" => "Invalid/expired token!"
        ]);
    }
}
