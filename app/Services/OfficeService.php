<?php

namespace App\Services;

use App\DTO\OfficeDTO;
use App\DTO\OfficeFilterDTO;
use App\Enums\OfficeApprovalStatus;
use App\Models\Office;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Pipeline;

class OfficeService
{
    public function filter(OfficeFilterDTO $officeFilterDTO, array $filters): Builder
    {
        return Pipeline::send($officeFilterDTO)
            ->through($filters)
            ->then(fn(OfficeFilterDTO $officeFilterDTO) => $officeFilterDTO->query);
    }

    public function store(OfficeDTO $officeDTO): Office
    {
        $office = DB::transaction(function () use ($officeDTO) {
            $office = Office::create([
                'user_id'           => $officeDTO->userId,
                'featured_image_id' => $officeDTO->featuredImageId,
                'title'             => $officeDTO->title,
                'description'       => $officeDTO->description,
                'lat'               => $officeDTO->lat,
                'lng'               => $officeDTO->lng,
                'address_line1'     => $officeDTO->addressLine1,
                'address_line2'     => $officeDTO->addressLine2,
                'approval_status'   => OfficeApprovalStatus::Pending,
                'is_hidden'         => $officeDTO->isHidden,
                'price_per_day'     => $officeDTO->pricePerDay,
                'monthly_discount'  => $officeDTO->monthlyDiscount,
            ]);

            if ($officeDTO->tagsIds !== null) {
                $office->tags()->attach($officeDTO->tagsIds);
            }

            return $office;
        });

        Notification::send(
            notifiables: User::admin()->get(),
            notification: new OfficePendingApproval($office),
        );

        return $office;
    }

    public function update(Office $office, OfficeDTO $officeDTO): Office
    {
        $office->fill([
            'featured_image_id' => $officeDTO->featuredImageId,
            'title'             => $officeDTO->title,
            'description'       => $officeDTO->description,
            'lat'               => $officeDTO->lat,
            'lng'               => $officeDTO->lng,
            'address_line1'     => $officeDTO->addressLine1,
            'address_line2'     => $officeDTO->addressLine2,
            'is_hidden'         => $officeDTO->isHidden,
            'price_per_day'     => $officeDTO->pricePerDay,
            'monthly_discount'  => $officeDTO->monthlyDiscount,
        ]);

        if ($requiresReview = $office->isDirty(['lat', 'lng', 'price_per_day'])) {
            $office->fill(['approval_status' => OfficeApprovalStatus::Pending]);
        }

        DB::transaction(function () use ($office, $officeDTO) {
            $office->save();

            if ($officeDTO->tagsIds !== null) {
                $office->tags()->sync($officeDTO->tagsIds);
            }
        });

        if ($requiresReview) {
            Notification::send(
                notifiables: User::admin()->get(),
                notification: new OfficePendingApproval($office),
            );
        }

        return $office;
    }
}
