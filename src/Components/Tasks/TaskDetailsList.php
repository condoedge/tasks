<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Query;
use Kompo\Tasks\Models\Task;
use Kompo\Tasks\Models\TaskDetail;

class TaskDetailsList extends Query
{
    public $perPage = 8;

    protected $taskId;
    protected $taskCardId;
    protected $forProjectHistory;

    public function created()
    {
        $this->taskId = $this->store('task_id');
        $this->taskCardId = $this->store('task_card_id');
        $this->forProjectHistory = $this->store('project_history') ?: false;

        $this->id('task-details-list-'.$this->taskId);
    }

    public function query()
    {
        return TaskDetail::with('user', 'files', 'relatedFiles.parent2')
            ->where('task_id', $this->taskId)->orderBy('created_at', 'DESC');
    }

    public function top()
    {
        // if(auth()->user()->isContact())
        //     return null;

        $toggleId = 'task-detail-form';

        $form = (new TaskDetailForm(null, [
            'task_id' => $this->taskId,
            'task_card_id' => $this->taskCardId,
            'file_initial_toggle' => $this->forProjectHistory ? false : true,
        ]))->id($toggleId);

        return !$this->forProjectHistory ? $form->class('bg-gray-100 border-b border-gray-200 p-4') :

            _Rows(
                _Link('Add')->icon(_Sax('add'))
                    ->class('absolute right-0')->style('top:-2.4rem')
                    ->toggleId($toggleId),
                $form
            )->class('relative');
    }

    public function render($td)
    {
        $allFiles = $td->allFiles();

    	return _Rows(
            _FlexBetween(
                _Html($td->created_at->diffForHumans()),
                _Html($td->created_at->format('d M Y H:i'))
            )->class('text-xs text-level2'),


            _FlexBetween(
                _Html($td->user?->name.' '.__('task.modified-action'))->class('text-xs text-gray-500 mb-2'),
                _FlexEnd(
                    auth()->user()->can('update', $td) ?
                        _Link()->icon(_Sax('edit',20))->class('mr-2')->selfUpdate('getTaskDetailForm', ['id' => $td->id])->inModal() :
                        null,
                    auth()->user()->can('delete', $td) ? _DeleteLink()->byKey($td) : null
                )
            )->class('text-level2'),

            _Html($td->details)->class('ck ck-content text-level2'),

            !$allFiles->count() ? null :

                _Flex(
                    $allFiles->map(function($file){
                        return $file->fileThumbnail();
                    })
                )->class('mt-2'),

            !$td->reminder_at ? null :

                _FlexEnd(
                    $td->completed_at ?

                        $this->completedButton('task.completed', 'resetTaskDetail', $td) :

                        (
                            // auth()->user()->isContact() ? null :
                            $this->completedButton('task.marked_complete', 'completeTaskDetail', $td)
                        )
                ),
        )->class('text-sm p-6 m-2 rounded-2xl bg-gray-100');
    }

    public function getTaskDetailForm($id)
    {
        return new TaskDetailForm($id, [
            'task_card_id' => $this->taskCardId,
            'no_task_closing' => $this->forProjectHistory,
            'injected_class' => 'm-4 py-4 card-white',
        ]);
    }

    protected function completedButton($label, $action, $taskDetail)
    {
        $button = _Button($label)->icon('icon-check')->outlined()
            ->class('border-gray-400 text-gray-500 mt-2 py-1 px-1 text-xs ');

        return 
            // auth()->user()->isContact() ? $button : 
            $button
                ->selfPost($action, ['id' => $taskDetail->id])
                ->refresh()
                ->browse(
                    array_merge(Task::taskListsToRefresh(), [
                        $this->taskCardId,
                    ])
                );
    }

    public function noItemsFound()
    {
        return _Html('task.text-here-will-be-the-note-details')
            ->class('my-4 px-6 mx-auto text-gray-600 text-center text-xs');
    }

    public function completeTaskDetail($id)
    {
        return TaskDetail::findOrFail($id)->complete();
    }

    public function resetTaskDetail($id)
    {
        return TaskDetail::findOrFail($id)->reset();
    }

}
