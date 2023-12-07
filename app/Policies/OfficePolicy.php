<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

class OfficePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tokenCan('office.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Office $office): bool
    {
        return $user->tokenCan('office.update') && $user->id === $office->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Office $office): bool
    {
        return $user->tokenCan('office.delete') && $user->id === $office->user_id;
    }
}
