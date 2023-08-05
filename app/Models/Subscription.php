<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Plan;

use Carbon\Carbon;


class Subscription extends Model
{
    use HasFactory;

    protected $hidden = [

    ];

    public function isActive()
    {
        return $this->status == 1;
    }

    public function isExpired()
    {
        return Carbon::now()->gte($this->expiring_at);
    }

    public function isValid()
    {
        return $this->isActive() && !$this->isExpired();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->hasOne(Plan::class);
    }
}
