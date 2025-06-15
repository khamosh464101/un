<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\Donor;

class DonorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Donor $donor): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('donor create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Donor $donor): bool
    {
        return $user->hasPermissionTo('donor update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Donor $donor): bool
    {
        return $user->hasPermissionTo('donor delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Donor $donor): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Donor $donor): bool
    {
        return false;
    }
}
