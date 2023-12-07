<?php

namespace App\DTO;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OfficeFilterDTO
{
    /**
     * @param array<int>|null $tagsIds
     */
    public function __construct(
        public readonly Builder $query,
        public readonly ?User   $user,
        public readonly ?int    $hostId,
        public readonly ?int    $visitorId,
        public readonly ?float  $lat,
        public readonly ?float  $lng,
        public readonly ?array  $tagsIds,
    )
    {
        //
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            Office::query(),
            $request->user(),
            $request->query('hostId'),
            $request->query('visitorId'),
            $request->query('lat'),
            $request->query('lng'),
            $request->query('tags'),
        );
    }
}
