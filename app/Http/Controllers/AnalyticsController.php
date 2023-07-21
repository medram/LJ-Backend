<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Chat;
use App\Models\Subscription;
use App\Models\Invoice;


class AnalyticsController extends Controller
{
    public function analytics(Request $request)
    {
        // All Customers
        $customers_count = User::where("role", 0)->count();

        // All Uploaded documents
        $documents_count = Chat::count();

        // All Subscriptions
        $subscriptions_count = Subscription::count();

        // All Active Subscriptions
        $active_subscriptions_count = Subscription::where("status", 1)->count();

        // All amount invoices
        $invoices_count = Invoice::count();

        // Recent customers
        $recent_customers = User::orderBy("created_at", "desc")->take(10)->get();

        // Recent subscriptions
        $recent_subscriptions = Subscription::select("subscriptions.*", "plans.name as plan_name", "plans.price", "plans.billing_cycle", "plans.is_free", "users.email as user_email", "users.username as user_username")
                    ->leftJoin("plans", "subscriptions.plan_id", "=", "plans.id")
                    ->leftJoin("users", "subscriptions.user_id", "=", "users.id")
                    ->orderBy("created_at", "desc")
                    ->take(10)
                    ->get();

        // Total revenue
        $total_revenue = round(Invoice::sum("amount"), 2);

        $analytics = [
            "total_revenue"         => $total_revenue,
            "customers_count"       => $customers_count,
            "documents_count"       => $documents_count,
            "subscriptions_count"   => $subscriptions_count,
            "invoices_count"        => $invoices_count,
            "recent_customers"      => $recent_customers,
            "recent_subscriptions"  => $recent_subscriptions,
            "active_subscriptions_count" => $active_subscriptions_count
        ];

        return response()->json([
            "errors" => false,
            "analytics" => $analytics
        ]);
    }
}
