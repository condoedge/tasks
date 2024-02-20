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
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
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
        return $taskDetail->user_id == $user->id;
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
        return $taskDetail->user_id == $user->id;
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
