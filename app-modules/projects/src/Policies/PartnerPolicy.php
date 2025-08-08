<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\Partner;

class PartnerPolicy
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
    public function view(User $user, Partner $partner): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('partner create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Partner $partner): bool
    {
        return $user->hasPermissionTo('partner update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Partner $partner): bool
    {
        return $user->hasPermissionTo('partner delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Partner $partner): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Partner $partner): bool
    {
        return false;
    }
}
