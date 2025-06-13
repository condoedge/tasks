<?php

namespace Kompo\Tasks\Components\Tasks;

use Condoedge\Utils\Kompo\Common\Query;
use Kompo\Tasks\Models\Contracts\Taskable;
use Kompo\Tasks\Models\TaskLink;

class TaskLinksCard extends Query
{
    public function created()
    {
        $this->id('task-participants-list-'.$this->store('task_id'));
    }

    public function query()
    {
        return TaskLink::where('task_id', $this->store('task_id'));
    }

    public function top()
    {
        return _MiniTitle('tasks.participants')->class('mb-2');
    }

    public function render($taskLink)
    {
        $taskable = $taskLink->taskable;

        if (!($taskable instanceof Taskable)) {
            throw new \Exception('Taskable must implement Taskable interface');
        } 

        $submenu = $taskable->getSubmenu()?->class('w-72 p-4');
        $name = $taskable->getName();

        return _FlexBetween(
            _Flex(
                _Sax('profile')->class('text-gray-600 mr-2'),
                _Html($name ?? 'tasks.incorrect-link')
            ),
            _FlexEnd(
                _Flex(
                    $taskable->getInfoElements(),
                )->class('gap-2'),
                _Delete($taskLink)->class('text-gray-500 mr-2 hover:text-red-700'),
                !$submenu ? null : _TripleDotsDropdown($submenu),
            )

        )->class('card-white-mbsmall py-3 px-4 text-sm mb-0 border');
    }

    public function noItemsFound()
    {
        return _Html('tasks.no-participants')->class('text-gray-600 text-sm');
    }
}
