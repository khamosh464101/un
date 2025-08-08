<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\Staff;

class StaffPolicy
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
    public function view(User $user, Staff $staff): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Staff $staff): bool
    {
        return $user->hasPermissionTo('staff update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Staff $staff): bool
    {
        return $user->hasPermissionTo('staff delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Staff $staff): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Staff $staff): bool
    {
        return false;
    }
}
