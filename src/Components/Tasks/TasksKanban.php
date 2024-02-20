<?php

namespace Kompo\Tasks\Components\Tasks;

use Illuminate\Support\Carbon;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Task;

class TasksKanban extends TasksMainView
{
	public $layout = 'Kanban';

	public $orderable = 'order';

    protected $switchToRouteName; // = 'tasks-manager'; deactivated until we really finish the other task view
    public $viewIcon = 'view-list';

    public $id = 'tasks-kanban';

	

    public function created()
    {
    	$this->columns = TaskStatusEnum::optionsWithLabels()->values()->toArray();
    	$this->columnStyle = 'min-width: 15rem; width: calc(25vw - 50px)';
    	$this->emptyColumn = _Html('task.drag_card')
    							->class('border-2 border-dashed border-gray-400 text-gray-600 text-center rounded-2xl py-6 mt-2');

    	$this->confirmBefore = [
	    	'status' => TaskStatusEnum::CLOSED,
	    	'attribute' => 'incomplete_task_details_min_reminder_at',
	    	'message' => __('task.incomplete-task-reminders'),
	    ];
    }

	public function query()
	{
        return parent::query()->where(function($q){

            $q->notClosed()->orWhere(function($q){

				$closedSinceDays = request('closed_since') ?: 2;

                $q->closed()->where('closed_at', '>=', Carbon::now()->addDays(-$closedSinceDays));

            });

        });
	}

	public function render($task)
	{
		return $task->taskCard()->class('bg-white rounded-2xl mt-2 hover:shadow-lg')
			->class($task->project ? 'border-l-4 border-level4' : ($task->maintenance ? 'border-l-4 border-positive' : 'border border-gray-200'))
            ->addClass('task-read')
            ->onSuccess(
            	fn($e) => $e->get('tasks.form', ['id' => $task->id])
	            	->inDrawer()
            );
	}

	public function changeStatus()
	{
		$task = Task::findOrFail(request('id'));

		if(!auth()->user()->can('update', $task))
			return;

		return $task->handleStatusChange(request('status'));
	}
}
