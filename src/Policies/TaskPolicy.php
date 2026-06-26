<?php

namespace Kompo\Tasks\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kompo\Tasks\Models\Task;
use Kompo\Auth\Models\Teams\PermissionTypeEnum;

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
        return in_array($task->team_id, $user->getAllAccessibleTeamIds());
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermission('Task', PermissionTypeEnum::WRITE);
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
        return ($task->created_by == $user->id) || $this->isTaskAssignedToUser($task, $user) || $user->hasPermission('UpdateOthersTask', PermissionTypeEnum::WRITE, teamIds: [$task->team_id]);
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
        return ($task->created_by == $user->id) || $user->hasPermission('DeleteOthersTask', PermissionTypeEnum::WRITE, teamIds: [$task->team_id]);
    }

    public function close(User $user, Task $task)
    {
        return $task->created_by == $user->id || 
            $this->isTaskAssignedToUser($task, $user) || 
            ($user->hasPermission('CloseOthersTask', PermissionTypeEnum::WRITE, teamIds: [$task->team_id]));
    }

    public function changeStatus(User $user, Task $task)
    {
        return $this->update($user, $task) || $this->isTaskAssignedToUser($task, $user);
    }

    protected function isTaskAssignedToUser(Task $task, User $user): bool
    {
        return $task->assigned_to === $user->id || $task->taskAssignations->flatMap(fn($a) => $a->getAllRelatedTaskUserAssignables())->pluck('id')->contains($user->id);
    }
}
