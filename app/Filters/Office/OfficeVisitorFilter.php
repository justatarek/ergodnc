<?php

namespace App\Filters\Office;

use App\DTO\OfficeFilterDTO;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OfficeVisitorFilter
{
    public function handle(OfficeFilterDTO $officeFilterDTO, Closure $next)
    {
        $officeFilterDTO->query->when(
            value: $officeFilterDTO->visitorId !== null,
            callback: fn(Builder $query) => $query->whereRelation('reservations', 'user_id', '=', $officeFilterDTO->visitorId),
        );

        return $next($officeFilterDTO);
    }
}
