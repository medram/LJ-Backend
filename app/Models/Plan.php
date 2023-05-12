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
        "status"
    ];


    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
