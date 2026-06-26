<?php
namespace Kompo\Tasks\Models;

use Condoedge\Utils\Models\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        $assignable = $this->assignableForTask();

        if (!$assignable) {
            return [];
        }

        if (method_exists($assignable, 'getAllRelatedTaskUserAssignables')) {
            return $assignable->getAllRelatedTaskUserAssignables($this->task_id);
        }

        return [$assignable];
    }

    // Using assignable it uses the main key by default
    // IN ROLE THE DEFAULT KEY IS AN STRING SO THAT BRING AN ERROR
    // That's the reason of this method, to be able to specify the key to use for the assignation
    public function assignableForTask()
    {
        $class = Relation::getMorphedModel($this->assignable_type) ?: $this->assignable_type;

        if (!$class || !class_exists($class)) {
            return null;
        }

        $model = new $class();

        return $class::query()
            ->where(static::taskAssignableKeyName($model), $this->assignable_id)
            ->first();
    }

    public static function taskAssignableKeyName($assignable)
    {
        if (method_exists($assignable, 'getKeyNameForTask')) {
            return $assignable->getKeyNameForTask();
        }

        return $assignable->getKeyName();
    }

    // SCOPES
    /**
     * Constrains assignations to those that resolve (directly or transitively) to the given user.
     * Operates on the task_assignations table, so it can be used inside whereHas() or as an
     * existence check on a task's assignations relation.
     */
    public function scopeRelatedToUser($query, $userId)
    {
        $classes = TaskAssignableRegistry::classes();

        if ($classes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($query) use ($classes, $userId) {
            $classes->each(function ($class) use ($query, $userId) {
                $model = new $class();
                $relatedQuery = $class::query();
                $relatedQuery = $class::getAllTaskRelatedToUserQuery($relatedQuery, $userId) ?: $relatedQuery;
                $taskKeyName = static::taskAssignableKeyName($model);

                $query->orWhere(function ($query) use ($model, $relatedQuery, $taskKeyName) {
                    $query->where('assignable_type', $model->getMorphClass())
                        ->whereIn('assignable_id', $relatedQuery->asSystemOperation()->select($model->qualifyColumn($taskKeyName)));
                });
            });
        })->asSystemOperation();
    }

    // ACTIONS
    public static function createForMany($taskId, $assignables)
    {
        foreach ($assignables as $assignable) {
            $taskAssignation = new TaskAssignation();
            $taskAssignation->task_id = $taskId;
            $taskAssignation->assignable_type = $assignable->getMorphClass();
            $taskAssignation->assignable_id = $assignable->getIdForTask();
            $taskAssignation->save();
        }
    }
}