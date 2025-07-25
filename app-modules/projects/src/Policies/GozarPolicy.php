<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\Gozar;

class GozarPolicy
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
    public function view(User $user, Gozar $gozar): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('location create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Gozar $gozar): bool
    {
        return $user->hasPermissionTo('location update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Gozar $gozar): bool
    {
        return $user->hasPermissionTo('location delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Gozar $gozar): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Gozar $gozar): bool
    {
        return false;
    }
}
