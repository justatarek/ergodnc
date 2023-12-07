<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'office_id'     => $this->office_id,
            'price'         => $this->price,
            'status'        => $this->status,
            'start_date'    => $this->start_date,
            'end_date'      => $this->end_date,
            'wifi_password' => $this->wifi_password,
            'user'          => UserResource::make($this->whenLoaded('user')),
            'office'        => OfficeResource::make($this->whenLoaded('office')),
        ];
    }
}
