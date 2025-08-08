<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\SubprojectType;

class SubprojectTypePolicy
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
    public function view(User $user, SubprojectType $subprojectType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('subproject type create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SubprojectType $subprojectType): bool
    {
        return $user->hasPermissionTo('subproject type update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubprojectType $subprojectType): bool
    {
        return $user->hasPermissionTo('subproject type delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SubprojectType $subprojectType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SubprojectType $subprojectType): bool
    {
        return false;
    }
}
