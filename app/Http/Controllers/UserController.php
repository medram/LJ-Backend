<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\AccessToken;
use App\Rules\StripTagsRule;

use Carbon\Carbon;

use DB;


class UserController extends Controller
{
    // Login user/customer.
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (Auth::attemptWhen($credentials, function (User $user) {
            return $user->is_active;
        }))
        {
            $user = $request->user();
            $tokens = $user->generateAccessToken();
            // update access token
            $token = $tokens["token"];
            $accessToken = $tokens["access_token"];
            $accessToken->last_used_at = Carbon::now();
            $accessToken->save();

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

    // Logout user/customer.
    public function logout(Request $request)
    {
        $token = userToken($request);
        $accessToken = AccessToken::where("token", hash('sha256', $token))->first();

        if ($accessToken)
        {
            $accessToken->delete();
        }

        // Used for sessions (Not required).
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'errors' => false,
            'message' => 'Logged out successfully.'
        ]);
    }

    // User dashboard insights (Not needed currently).
    public function dashboard(Request $request)
    {
        return response()->json([
            "dashboard_data" => $request->user()
        ]);
    }

    // Get User/Customer profile details.
    public function profile(Request $request)
    {
        return response()->json([
            "error" => false,
            "user" => $request->user()
        ]);
    }

    // Update User/Customer profile details.
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

    // Get User/Customer recent valid subscription.
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

    // List all available User/Customer's invoices.
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

    // Register User/Customer.
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
            if (isDemo())
            {
                $user->is_active = 1; // activate the user automatically on demo mode.
                $user->save();

                return response()->json([
                    "error" => false,
                    "message" => "Registered Successfully, No need for email verification on the demo mode."
                ], 201);
            }
            else
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
        }


        return response()->json([
            "error" => false,
            "message" => "Registered Successfully, please check you email inbox for account activation."
        ], 201);
    }

    // Verify User/Customer's account.
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

    // Get Current Logged in User/Customer.
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

    // Update User/Customer's Password.
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

    // Sent "Forget Password" email to reset User/Customer's password.
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

    // Rest User/Customer's password.
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

    // Activate Free Plan for a specific user.
    public function activateFreePlan(Request $request)
    {
        $fields = $request->validate([
            "plan_id" => "string|required", // could be integer
        ]);

        $plan_id = $fields["plan_id"];

        $plan = Plan::where("id", intval($plan_id))->first();
        $user = $request->user();

        if ($plan && $plan->isFree() && $user)
        {
            // register new free subscription for this user
            $duration = $plan->billing_cycle == "monthly"? 30 : 365;
            $subscription = new Subscription();
            $subscription->sub_id = Str::random(10);
            $subscription->user_id = $user->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 1; // active
            $subscription->expiring_at = Carbon::now()->addDays($duration);

            $subscription->pdfs = $plan->pdfs;
            $subscription->questions = $plan->questions;
            $subscription->pdf_size = $plan->pdf_size;

            $subscription->save();

            return response()->json([
                "errors" => false,
                "message" => "Subscribed successfully."
            ]);
        }

        return response()->json([
            "errors" => true,
            "message" => "Plan Not Found/Free"
        ]);
    }
}
