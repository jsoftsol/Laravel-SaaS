<?php
namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // Admin & Manager can view all tasks in their company
        if ($user->hasRole(['admin', 'manager'])) {
            return $task->project->company_id === $user->company_id;
        }

        // Developer can only view assigned tasks
        return $task->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Admin & Manager can update anything in their company
        if ($user->hasRole(['admin', 'manager'])) {
            return $task->project->company_id === $user->company_id;
        }

        // Developer can only update status of own tasks
        return $task->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }

    public function assign(User $user)
    {
        // Only Admin & Manager can assign tasks
        return $user->hasRole(['admin', 'manager']);
    }
}
