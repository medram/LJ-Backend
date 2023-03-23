<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Subscription;


class Plan extends Model
{
    use HasFactory;

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
