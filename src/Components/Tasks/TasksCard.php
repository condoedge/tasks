<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Query;
use Kompo\Tasks\Facades\TaskModel;

class TasksCard extends Query
{
    public $perPage = 4;
    public $paginationType = 'Scroll';
    public $itemsWrapperClass = 'overflow-y-auto mini-scroll';
    public $itemsWrapperStyle = 'max-height: 350px';

    public $noItemsFound = ' ';

    const ID = 'intro-tasks-card';
	public $id = self::ID;

    public $class = 'intro-tasks-card dashboard-card';

    protected $onlyOfUser = false;

    public function created()
    {
        $this->onlyOfUser = (boolean) $this->prop('only_of_user');
    }

    public function query()
    {
        return TaskModel::baseQuery()
                ->when($this->onlyOfUser, fn($q) => $q->mine())
                ->forTeam()
                ->closed();
    }

    public function top()
    {
        $title = 
            // auth()->user()->isContact() ? 'Follow-ups-tasks' : 
            'Tasks';

        return _CardHeader($title, !auth()->user()->can('create', TaskModel::getClass()) ? null : [
            _Link()->icon(_Sax('clipboard-tick',20))->href('tasks.kanban')
                ->class('mr-2 text-xl')->balloon('tasks.see_all_tasks','up'),
            _IconFilter('urgent', 'info-circle', 'tasks.filter_urgent')
                ->class('mr-2'),
            _Link()->icon(_Sax('add'))->balloon('Add')
                ->get($this->editRoute, $this->routeParameters())
                ->inDrawer()
        ]);
    }

    public function render($task)
    {
        return $task->taskCard()
            ->class('border-b border-gray-200')
            ->get($this->editRoute, $this->routeParameters(['id' => $task->id]))
            ->inDrawer();
    }
}
