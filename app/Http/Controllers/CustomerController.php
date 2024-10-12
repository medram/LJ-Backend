<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Rules\StripTagsRule;

class CustomerController extends Controller
{
    // List all available customers
    public function customers(Request $request)
    {
        $customers = User::where('role', 0)->orderBy('id', 'DESC')->get();

        return response()->json([
            'errors' => false,
            'customers' => $customers
        ]);
    }

    // add a new customer
    public function add(Request $request)
    {
        $request->validate([
            "username"  => ["required", "min:4", "max:25", new StripTagsRule()],
            "email"     => "required|email|unique:users",
            "password"  => "required|min:6|max:40",
            "is_active" => "required",
        ]);

        $data = $request->all();

        $data['name'] = $data['username'];
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // TODO: Send email verification.

        return response()->json([
            "errors" => false,
            "message" => "Added Successfully!"
        ], 201);
    }

    // Edit a specific customer
    public function edit(Request $request, $id)
    {
        $request->validate([
            "username"  => ["required", "min:4", "max:25", new StripTagsRule()],
            "email" => "required|email",
            "password" => "string|nullable",
            "is_active" => "boolean"
        ]);

        $fields = $request->all();

        $customer = User::where('id', $id)->first();
        $customer->username = $fields['username'];
        $customer->email = $fields['email'];
        $customer->is_active = intval($fields['is_active']);

        if ($fields['password']) {
            $customer->password = Hash::make($fields['password']);
        }

        if ($customer->save()) {
            return response()->json([
                "errors" => false,
                "message" => "Updated successfully."
            ]);
        }

        return response()->json([
            "errors" => true,
            "message" => "Something went wrong!"
        ]);
    }

    // Get customer's details
    public function details(Request $request, $id)
    {
        $customer = User::where('id', $id)->first();

        return response()->json([
            "errors" => false,
            "customer" => $customer
        ]);
    }

    // delete customer
    public function delete(Request $request)
    {
        try {
            $customer = User::where('id', $request->id)->first();

            if ($customer) {
                $customer->delete();
            }

            return response()->json([
                "errors" => false,
                "message" => "Deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "errors" => true,
                "message" => "Something went wrong while deletion!"
            ]);
        }
    }
}
