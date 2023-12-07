<?php

namespace App\Models;

use App\Enums\OfficeApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    // Config
    protected $fillable = [
        'user_id',
        'featured_image_id',
        'title',
        'description',
        'lat',
        'lng',
        'address_line1',
        'address_line2',
        'approval_status',
        'is_hidden',
        'price_per_day',
        'monthly_discount',
    ];

    protected $casts = [
        'lat'              => 'decimal:8',
        'lng'              => 'decimal:8',
        'approval_status'  => OfficeApprovalStatus::class,
        'is_hidden'        => 'boolean',
        'price_per_day'    => 'integer',
        'monthly_discount' => 'integer',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'featured_image_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', OfficeApprovalStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', OfficeApprovalStatus::Approved);
    }

    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function scopeNearestTo(Builder $query, float $lat, float $lng): Builder
    {
        return $query->orderByRaw(
            sql: 'POW(69.1 * (lat - ?), 2) + POW(69.1 * (? - lng) * COS(lat / 57.3), 2)',
            bindings: [$lat, $lng],
        );
    }
}
