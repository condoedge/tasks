<?php

namespace Kompo\Tasks\Components\Tasks;

class TasksManager extends TasksMainView
{
	public $perPage = 10;

    public $itemsWrapperClass = 'overflow-y-auto mini-scroll';
    public $itemsWrapperStyle = 'height: calc(100vh - 200px)';

    protected $switchToRouteName = 'tasks.kanban';
    public $viewIcon = 'view-grid';

	public function right()
	{
		return _Panel(
			_DashedBox('Click on an item to visualize it.')->class('dashedBox')
		)->id('showPagePanel')
		->class('overflow-y-auto mini-scroll')
		->animate('slideLeft')->closable();
	}

	public function render($task)
	{
		return $task->taskCard()->class('w-80 bg-white')
			->get('task.form', ['id' => $task->id])
            ->inPanel('showPagePanel');
	}
}