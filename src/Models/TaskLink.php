<?php

namespace Kompo\Tasks\Models;

use Condoedge\Utils\Models\Model;
use Kompo\Tasks\Facades\TaskModel;

class TaskLink extends Model
{
    /* ATTRIBUTES */
    
    /* RELATIONSHIPS */
    public function taskable()
    {
    	return $this->morphTo();
    }

	public function task()
	{
		return $this->belongsTo(TaskModel::getClass());
	}

    /* ACTIONS */
    public static function insertIfNew($taskId, $taskableId, $taskableType)
    {
    	if(
    		static::where('task_id', $taskId)
    		->where('taskable_type', $taskableType)
    		->where('taskable_id', $taskableId)
    		->count()
    	)
    		return;

    	$tl = new static();
		$tl->task_id = $taskId;
		$tl->taskable_type = $taskableType;
		$tl->taskable_id = $taskableId;
		$tl->save();
    }
}
