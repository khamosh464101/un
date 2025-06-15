<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Projects\Models\TicketStatus;

class TicketStatusPolicy
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
    public function view(User $user, TicketStatus $taskStatus): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('task status create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TicketStatus $taskStatus): bool
    {
        return $user->hasPermissionTo('task status update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TicketStatus $taskStatus): bool
    {
        return $user->hasPermissionTo('task status delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TicketStatus $taskStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TicketStatus $taskStatus): bool
    {
        return false;
    }
}
