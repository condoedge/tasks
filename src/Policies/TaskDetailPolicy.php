<?php

namespace Kompo\Tasks\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kompo\Tasks\Models\TaskDetail;

class TaskDetailPolicy
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
     * @param  \Kompo\Tasks\Models\TaskDetail  $taskDetail
     * @return mixed
     */
    public function view(User $user, TaskDetail $taskDetail)
    {
    }

    public function viewFileOf(User $user, TaskDetail $taskDetail)
    {
        return in_array($user->id, [$taskDetail->added_by, $taskDetail->modified_by, $taskDetail->user_id]);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermission('taskDetails:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\TaskDetail  $taskDetail
     * @return mixed
     */
    public function update(User $user, TaskDetail $taskDetail)
    {
        return $taskDetail->created_by == $user->id || 
            ($taskDetail->task->team_id == $user->current_team_id && $user->hasPermission('taskDetails:updateOfTeam')) ||
            $user->hasPermission('taskDetails:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\TaskDetail  $taskDetail
     * @return mixed
     */
    public function delete(User $user, TaskDetail $taskDetail)
    {
        return $taskDetail->created_by == $user->id || 
            ($taskDetail->task->team_id == $user->current_team_id && $user->hasPermission('taskDetails:deleteOfTeam')) ||
            $user->hasPermission('taskDetails:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\TaskDetail  $taskDetail
     * @return mixed
     */
    public function restore(User $user, TaskDetail $taskDetail)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \Kompo\Tasks\Models\TaskDetail  $taskDetail
     * @return mixed
     */
    public function forceDelete(User $user, TaskDetail $taskDetail)
    {
        //
    }
}
