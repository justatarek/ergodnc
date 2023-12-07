<?php

namespace App\Filters\Office;

use App\DTO\OfficeFilterDTO;
use App\Enums\OfficeApprovalStatus;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OfficeHostFilter
{
    public function handle(OfficeFilterDTO $officeFilterDTO, Closure $next)
    {
        $officeFilterDTO
            ->query
            ->when(
                value: $officeFilterDTO->hostId !== null && $officeFilterDTO->user?->id === $officeFilterDTO->hostId,
                callback: fn(Builder $query) => $query,
                default: fn(Builder $query) => $query->where('approval_status', OfficeApprovalStatus::Approved)->where('is_hidden', false),
            )
            ->when(
                value: $officeFilterDTO->hostId !== null,
                callback: fn(Builder $query) => $query->where('user_id', $officeFilterDTO->hostId),
            );

        return $next($officeFilterDTO);
    }
}
