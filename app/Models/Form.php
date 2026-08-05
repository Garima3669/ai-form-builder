<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'source',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)
            ->orderBy('sort_order');
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class);
    }
}