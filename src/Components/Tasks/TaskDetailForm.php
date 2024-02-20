<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Auth\Models\Files\File;
use Kompo\Form;
use Kompo\Tasks\Components\General\CKEditorExtended;
use Kompo\Tasks\Models\Task;
use Kompo\Tasks\Models\TaskDetail;
use Kompo\Tasks\Models\TaskLink;

class TaskDetailForm extends Form
{
	public $model = TaskDetail::class;

	public $style = 'min-width:300px';
    public $class = 'bg-gray-300 rounded-2xl mx-2 px-6 py-4';

	protected $taskId;
	protected $task;
	protected $taskCardId;
	protected $fileInitialToggle;
	protected $noTaskClosing;

	public function created()
	{
		$this->taskId = $this->store('task_id') ?: $this->model->task_id;
		$this->task = Task::find($this->taskId);
		$this->taskCardId = $this->store('task_card_id');

		$this->fileInitialToggle = $this->prop('file_initial_toggle');
		$this->noTaskClosing = $this->prop('no_task_closing');

        $this->class($this->prop('injected_class'));
	}

	public function beforeSave()
	{
		$this->model->setUserId();

		if(!$this->model->task_id) //if editing keep old task_id
			$this->model->task_id = $this->taskId;
	}

	public function afterSave()
	{
		$this->model->addLinkedFiles(request('selected_files'));

		$this->processTaskDetails();

		$this->model->markRead();
	}

	public function render()
	{
		[$attachmentsLink, $attachmentsBox] = File::fileUploadLinkAndBox('files', is_null($this->fileInitialToggle) ? !$this->model->allFiles()->count() : $this->fileInitialToggle
		// , $this->model->filesFromRelations()->pluck('id')
		);

		return [
			_CKEditorExtended('')->name('details')->class('ckNoToolbar'),
			$attachmentsBox,
	        _FlexEnd2(
                _Flex2 (
				$attachmentsLink,
        		($this->noTaskClosing || $this->task->isClosed()) ? null :

	        		_SubmitButton('task.add-and-close-task')->class('mr-2 mb-2 md:mb-0 w-full md:w-auto')->outlined()
	        			->onSuccess(function($e){
		        			$e->selfPost('closeTask')
		        				->refresh('task-adding-view')
		        				->browse(
		        					array_merge(Task::taskListsToRefresh(), [
		        						$this->taskCardId,
									])
		        				);
	        			})
                    )->class('w-full md:w-auto'),

            	_SubmitButton('Add')->class('mr-2 w-full md:w-auto')
		        	->browse(
		        		array_merge(Task::taskListsToRefresh(), [
			        		'task-participants-list-'.$this->taskId,
			        		$this->taskCardId,
						])
		        	)->refresh('task-details-list-'.$this->taskId)
	        )->class('flex-wrap'),
	    ];
	}

	public function rules()
	{
		return [
			'details' => 'required'
		];
	}

	protected function processTaskDetails()
	{
		$this->model->reminder_at = null;
		$this->model->save();

		CKEditorExtended::parseText(request('details'), 'Task Detail (ID'.$this->model->id.')')
			->each(function($mention){

				if(count($mention) == 3){

					TaskLink::insertIfNew($this->model->task_id, $mention[1], strtolower($mention[2]));


					if ($mention[2] == 'user') {
						// $this->model->task->notify($mention[1]);
					}

				}elseif(count($mention) == 2){

					$td = TaskDetail::find($this->model->id);
					if(!$td->reminder_at || ($mention[1] < $td->reminder_at)){
						$td->reminder_at = $mention[1];
						$td->save();
					}

				}
			});
	}


	public function closeTask()
	{
		return Task::findOrFail($this->taskId)->close();
	}

}
