<?php

namespace App\Filters\Office;

use App\DTO\OfficeFilterDTO;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OfficeCoordinatesFilter
{
    public function handle(OfficeFilterDTO $officeFilterDTO, Closure $next)
    {
        $officeFilterDTO->query->when(
            value: $officeFilterDTO->lat !== null && $officeFilterDTO->lng !== null,
            callback: fn(Builder $query) => $query->nearestTo($officeFilterDTO->lat, $officeFilterDTO->lng),
            default: fn(Builder $query) => $query->oldest('id'),
        );

        return $next($officeFilterDTO);
    }
}
