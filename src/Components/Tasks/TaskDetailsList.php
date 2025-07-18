<?php

namespace Kompo\Tasks\Components\Tasks;

use Condoedge\Utils\Kompo\Common\Query;
use Kompo\Tasks\Facades\TaskDetailModel;
use Kompo\Tasks\Facades\TaskModel;

class TaskDetailsList extends Query
{
    public $perPage = 8;

    protected $taskId;
    protected $taskCardId;

    public function created()
    {
        $this->taskId = $this->store('task_id');
        $this->taskCardId = $this->store('task_card_id');

        $this->id('task-details-list-'.$this->taskId);
    }

    public function query()
    {
        return TaskDetailModel::with('user', 'files')
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
            'file_initial_toggle' => true,
        ]))->id($toggleId);

        return $form->class('bg-gray-100 border-b border-gray-200 p-4');
    }

    public function render($td)
    {
        $allFiles = $td->getRelatedFiles();

    	return _Rows(
            _FlexBetween(
                _Html($td->created_at->diffForHumans()),
                _Html($td->created_at->format('d M Y H:i'))
            )->class('text-xs text-level1'),

            _FlexBetween(
                _Html($td->user?->name.' '.__('tasks.modified-action'))->class('text-xs text-level1 opacity-60 mb-2'),
                _FlexEnd(
                    auth()->user()->can('update', $td) ?
                        _Link()->icon(_Sax('edit',20))->class('mr-2')->selfUpdate('getTaskDetailForm', ['id' => $td->id])->inModal() :
                        null,
                    auth()->user()->can('delete', $td) ? _Delete($td) : null
                )
            )->class('text-level1'),

            _Html($td->details)->class('ck ck-content text-black'),

            !$allFiles->count() ? null :

                _Flex(
                    $allFiles->map(fn($file) => $file->fileThumbnail())
                )->class('flex-wrap mt-2'),

            !$td->reminder_at ? null :

                _FlexEnd(
                    $td->completed_at ?

                        $this->completedButton('tasks.completed', 'resetTaskDetail', $td) :

                        (
                            // auth()->user()->isContact() ? null :
                            $this->completedButton('tasks.mark-as-completed', 'completeTaskDetail', $td)
                        )
                ),
        )->class('text-sm p-6 m-2 rounded-2xl bg-level4 bg-opacity-30');
    }

    public function getTaskDetailForm($id)
    {
        return new TaskDetailForm($id, [
            'task_card_id' => $this->taskCardId,
            'no_task_closing' => false,
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
                    array_merge(TaskModel::taskListsToRefresh(), [
                        $this->taskCardId,
                    ])
                );
    }

    public function noItemsFound()
    {
        return _Html('tasks.text-here-will-be-the-note-details')
            ->class('my-4 px-6 mx-auto text-gray-600 text-center text-xs');
    }

    public function completeTaskDetail($id)
    {
        return TaskDetailModel::findOrFail($id)->complete();
    }

    public function resetTaskDetail($id)
    {
        return TaskDetailModel::findOrFail($id)->reset();
    }

}
