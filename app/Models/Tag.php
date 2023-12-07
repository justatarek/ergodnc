<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    // Config
    protected $fillable = [
        'name',
    ];

    // Relations
    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class);
    }
}
