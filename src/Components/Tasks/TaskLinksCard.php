<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Query;
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
        return _MiniTitle('Participants')->class('mb-2');
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
                _Html($name ?? 'Incorrect Link')
            ),
            _FlexEnd(
                _DeleteLink()->byKey($taskLink)->class('text-gray-300 mr-2'),
                !$submenu ? null : _TripleDotsDropdown($submenu),
            )

        )->class('card-white-mbsmall py-3 px-4 text-sm mb-0 border');
    }

    public function noItemsFound()
    {
        return _Html('task.no_participants')->class('text-gray-600 text-sm');
    }
}
