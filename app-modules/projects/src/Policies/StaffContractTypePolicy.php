<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\StaffContractType;

class StaffContractTypePolicy
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
    public function view(User $user, StaffContractType $staffContractType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('staff contract type create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StaffContractType $staffContractType): bool
    {
        return $user->hasPermissionTo('staff contract type update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StaffContractType $staffContractType): bool
    {
        return $user->hasPermissionTo('staff contract type delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StaffContractType $staffContractType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StaffContractType $staffContractType): bool
    {
        return false;
    }
}
