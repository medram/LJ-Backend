<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User;


class Images extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'prompt',
        'resolution',
        'path'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
