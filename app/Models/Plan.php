<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Subscription;


class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "description",
        "billing_cycle",
        "is_free",
        "is_popular",
        "pdf_pages",
        "pdf_size",
        "pdfs",
        "price",
        "questions",
        "paypal_plan_id",
        "stripe_plan_id",
        "features",
        "status",
        "soft_delete"
    ];

    protected $casts = [
        "is_free"       => "boolean",
        "is_popular"    => "boolean",
        "is_popular"    => "boolean",
        "price"         => "float",
        "status"        => "integer",
        "pdfs"          => "integer",
        "questions"     => "integer",
        "pdf_size"      => "float",
        "pdf_pages"     => "integer",
        "soft_delete"   => "integer",
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->is_free || $this->price == 0;
    }
}
