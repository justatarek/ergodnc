<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    // Config
    protected $fillable = [
        'user_id',
        'office_id',
        'price',
        'status',
        'start_date',
        'end_date',
        'wifi_password',
    ];

    protected $casts = [
        'price'         => 'integer',
        'status'        => ReservationStatus::class,
        'start_date'    => 'immutable_date',
        'end_date'      => 'immutable_date',
        'wifi_password' => 'encrypted'
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Active);
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Canceled);
    }

    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->where(function (Builder $query) use ($from, $to) {
            $query
                ->whereBetween('start_date', [$from, $to])
                ->orWhereBetween('end_date', [$from, $to])
                ->orWhere(function (Builder $query) use ($from, $to) {
                    $query
                        ->where('start_date', '<', $from)
                        ->where('end_date', '>', $to);
                });
        });
    }

    public function scopeActiveBetween(Builder $query, string $from, string $to): Builder
    {
        return $query
            ->where('status', ReservationStatus::Active)
            ->betweenDates($from, $to);
    }
}
