<?php

namespace Kompo\Tasks\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kompo\Tasks\Models\Task;
use Kompo\Tasks\Models\TaskDetail;
use Kompo\Auth\Models\Teams\PermissionTypeEnum;

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
        return in_array($user->id, [$taskDetail->added_by, $taskDetail->modified_by, $taskDetail->user_id]) || $this->isTaskAssignedToUser($taskDetail->task, $user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, Task $task)
    {
        return $task->created_by == $user->id || $this->isTaskAssignedToUser($task, $user) || $user->hasPermission('CreateOthersTaskDetail', PermissionTypeEnum::WRITE, teamIds: [$task->team_id]);
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
        return $taskDetail->created_by == $user->id;
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
            $user->hasPermission('DeleteOthersTaskDetail', PermissionTypeEnum::WRITE, teamIds: [$taskDetail->task->team_id]);
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

    protected function isTaskAssignedToUser(Task $task, User $user): bool
    {
        return $task->assigned_to === $user->id || $task->taskAssignations->flatMap(fn($a) => $a->getAllRelatedTaskUserAssignables())->pluck('id')->contains($user->id);
    }
}
