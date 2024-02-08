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

    const EXPIRED = 0;
    const ACTIVE = 1;
    const CANCELED = 2;
    const SUSPENDED = 3;

    protected $hidden = [

    ];

    protected $casts = [
        "user_id"   => "integer",
        "plan_id"   => "integer",
        "status"    => "integer",
        "pdfs"      => "integer",
        "questions" => "integer",
        "pdf_size"  => "float",
        "pdf_pages" => "integer",
        "price"     => "float",
        "is_free"   => "boolean",
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
