<?php
namespace Kompo\Tasks\Models;

use Condoedge\Utils\Models\Model;

class TaskAssignation extends Model
{
    protected $table = 'task_assignations';

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignable()
    {
        return $this->morphTo();
    }

    public function getAllRelatedTaskUserAssignables()
    {
        if (method_exists($this->assignable, 'getAllRelatedTaskUserAssignables')) {
            return $this->assignable->getAllRelatedTaskUserAssignables($this->task_id);
        }

        return [$this->assignable];
    }

    // ACTIONS
    public static function createForMany($taskId, $assignables)
    {
        foreach ($assignables as $assignable) {
            $taskAssignation = new TaskAssignation();
            $taskAssignation->task_id = $taskId;
            $taskAssignation->assignable_type = $assignable->getMorphClass();
            $taskAssignation->assignable_id = $assignable->getKey();
            $taskAssignation->save();
        }
    }
}