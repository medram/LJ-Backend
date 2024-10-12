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

    public const EXPIRED = 0;
    public const ACTIVE = 1;
    public const CANCELED = 2;
    public const SUSPENDED = 3;
    public const UPGRADED = 4;

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
        // return $this->belongsTo(User::class);
        return User::find($this->user_id)->first();
    }

    public function plan()
    {
        return $this->hasOne(Plan::class);
    }
}
