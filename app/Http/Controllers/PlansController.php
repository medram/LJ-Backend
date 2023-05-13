<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Plan;
use App\Rules\StripTagsRule;


class PlansController extends Controller
{

    public function list()
    {
        //$plans = Plan::where('status', 1)->get();
        $plans = Plan::all();

        return response()->json([
            'errors' => false,
            'plans' => $plans
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            "name" => ["string", "required", "max:50", new StripTagsRule],
            "description" => ["string", "nullable", "max:50", new StripTagsRule],
            "price" => "numeric",
            "is_popular" => "boolean",
            "is_free" => "boolean",
            "billing_cycle" => Rule::in(['monthly', 'yearly']),
            "status" => "boolean",
            "pdfs" => "integer",
            "pdf_size" => "numeric",
            "pdf_pages" => "integer",
            "questions" => "integer",
        ]);

        try {
            $plan = Plan::create($request->all());

            return response()->json([
                'errors' => false,
                'message' => "Create successfully.",
                'plans' => $plan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => true,
                'message' => "Something went wrong."
            ]);
        }
    }

    public function edit(Request $request, $id)
    {
        $plan = plan::where('id', $id)->get()->first();

        if ($plan)
        {
            $request->validate([
                "name" => ["string", "required", "max:50", new StripTagsRule],
                "description" => ["string", "nullable", "max:50", new StripTagsRule],
                "price" => "numeric",
                "is_popular" => "boolean",
                "is_free" => "boolean",
                "billing_cycle" => Rule::in(['monthly', 'yearly']),
                "status" => "boolean",
                "pdfs" => "integer",
                "pdf_size" => "numeric",
                "pdf_pages" => "integer",
                "questions" => "integer",
            ]);

            try {
                $plan->update($request->all());

                return response()->json([
                    'errors' => false,
                    'message' => "Updated successfully.",
                    'plan' => $plan
                ], 200);
            } catch (\Exception $e) {
                echo $e;
                return response()->json([
                    'errors' => true,
                    'message' => "Something went wrong."
                ]);
            }

        }

        return response()->json([
            'errors' => true,
            'message' => "Plan not found."
        ]);
    }
}
