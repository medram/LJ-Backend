<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;


class CustomerController extends Controller
{
    public function customers(Request $request)
    {
        $customers = User::where('role', 0)->orderBy('id', 'DESC')->get();

        return response()->json([
            'errors' => false,
            'customers' => $customers
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            "username"  => "required|min:4|max:25",
            "email"     => "required|email|unique:users",
            "password"  => "required|min:6|max:40",
            "is_active" => "required",
        ]);

        $data['name'] = $data['username'];
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // TODO: Send email verification.

        return response()->json([
            "errors" => false,
            "message" => "Added Successfully!"
        ], 201);
    }

    public function edit(Request $request, $id)
    {
        $fields = $request->validate([
            "username"  => "required|string|min:4",
            "email" => "required|email",
            "password" => "string|nullable",
            "is_active" => "boolean"
        ]);

        $customer = User::where('id', $id)->first();
        $customer->username = $fields['username'];
        $customer->email = $fields['email'];
        $customer->is_active = intval($fields['is_active']);

        if ($fields['password'])
            $customer->password = Hash::make($fields['password']);

        if ($customer->save())
        {
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

    public function details(Request $request, $id)
    {
        $customer = User::where('id', $id)->first();

        return response()->json([
            "errors" => false,
            "customer" => $customer
        ]);
    }

    public function delete(Request $request)
    {
        try {
            $customer = User::where('id', $request->id)->first();

            if ($customer)
                $customer->delete();

            return response()->json([
                "errors" => false,
                "message" => "Deleted successfully"
            ]);
        } catch (\Exception $e){
            return response()->json([
                "errors" => true,
                "message" => "Something went wrong while deletion!"
            ]);
        }
    }
}
