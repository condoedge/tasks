<?php

namespace Kompo\Tasks\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kompo\Tasks\Models\Task;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\Task  $task
     * @return mixed
     */
    public function view(User $user, Task $task)
    {
        return $task->team_id == $user->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermission('tasks:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\Task  $task
     * @return mixed
     */
    public function update(User $user, Task $task)
    {
        return ($task->created_by == $user->id) || 
            ($user->hasPermission('tasks:updateOfTeam') && $task->team_id == $user->current_team_id) || 
            $user->hasPermission('tasks:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\Task  $task
     * @return mixed
     */
    public function delete(User $user, Task $task)
    {
        return ($task->created_by == $user->id) || 
            ($user->hasPermission('tasks:deleteOfTeam') && $task->team_id == $user->current_team_id) || 
            $user->hasPermission('tasks:delete');
    }

    public function close(User $user, Task $task)
    {
        return $task->created_by == $user->id || 
            ($task->assigned_to == $user->id && $user->can('tasks:closeAssigned')) || 
            ($user->hasPermission('tasks:closeOfTeam') && $task->team_id == $user->current_team_id) || 
            $user->hasPermission('tasks:close');
    }
}
