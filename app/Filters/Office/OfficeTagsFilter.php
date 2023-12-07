<?php

namespace App\Filters\Office;

use App\DTO\OfficeFilterDTO;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OfficeTagsFilter
{
    public function handle(OfficeFilterDTO $officeFilterDTO, Closure $next)
    {
        $tagsCount = count($officeFilterDTO->tagsIds ?? []);

        $officeFilterDTO->query->when(
            value: $tagsCount > 0,
            callback: fn(Builder $query) => $query->whereHas(
                relation: 'tags',
                callback: fn($query) => $query->whereIn('id', $officeFilterDTO->tagsIds),
                operator: '=',
                count: $tagsCount,
            ),
        );

        return $next($officeFilterDTO);
    }
}
