<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\ProjectStatus;

class ProjectStatusPolicy
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
    public function view(User $user, ProjectStatus $projectStatus): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('project status create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProjectStatus $projectStatus): bool
    {
        return $user->hasPermissionTo('project status update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectStatus $projectStatus): bool
    {
        return $user->hasPermissionTo('project status delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectStatus $projectStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectStatus $projectStatus): bool
    {
        return false;
    }
}
