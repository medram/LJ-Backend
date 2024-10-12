<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Plan;

class Invoice extends Model
{
    use HasFactory;

    public const UNPAID = 0;
    public const PAID = 1;
    public const REFUNDED = 2;

    protected $hidden = [];

    protected $casts = [
        "user_id"   => "integer",
        "plan_id"   => "integer",
        "status"    => "integer",
        "amount"    => "float",
        "paid_at"   => "datetime",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->hasOne(Plan::class);
    }
}
