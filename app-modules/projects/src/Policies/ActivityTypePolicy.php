<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\ActivityType;

class ActivityTypePolicy
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
    public function view(User $user, ActivityType $activityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('activity type create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActivityType $activityType): bool
    {
        return $user->hasPermissionTo('activity type update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActivityType $activityType): bool
    {
        return $user->hasPermissionTo('activity type delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ActivityType $activityType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ActivityType $activityType): bool
    {
        return false;
    }
}
