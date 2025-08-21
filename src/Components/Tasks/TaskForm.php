<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Tasks\Facades\TaskDetailModel;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskDetail;

class TaskForm extends TaskInfoForm
{
    protected $slug = 'tasks';

	protected $refresh = true;
	
	protected $threadId;

	public $class = 'bg-white rounded-l-2xl';

	public function created()
	{		
        $this->id('task-adding-view');

		$this->style = 
			// auth()->user()->isContact() ? 'min-width: 50vw; max-width: 668px' :
							'min-width: 75vw; max-width: 960px';

		$this->store([
			'redirectBackTo' => $this->store('redirectBackTo') ?: url()->previous()
		]);

		$this->model->markAsRead();

        // if($this->model->notifications()->forAuthUser()->count())
        //     $this->model->notifications()->forAuthUser()->get()->each->markSeen();

		if($this->model->id)
			$this->_kompo('options', [
				'refresh' => false
			]);

		if ($this->threadId = $this->prop('thread_id')) {

			$thread = \Condoedge\Messaging\Models\CustomInbox\Thread::findOrFail($this->threadId);

			$task = Task::where('from_thread_id', $this->threadId)->first();

			if (!$task) {

				$task = $this->createTaskAndDetailFromThread($thread);

				$this->onLoad(fn($e) => $e->alert('activity.new-task-created'));

				$this->tagIds = $thread->tags;
			}

			$this->model($task);

		}
	}

	public function render()
	{
		return $this->panelWrapper($this->model->title ?: 'tasks.add-task', 'annotation',

			$this->singleColumn() ? null : $this->taskInfoElements()->col('col-md-5'),

			_Rows(

                $this->rightColumn()

			)->col($this->singleColumn() ? 'col-md-12' : 'col-md-7')
		);
	}

	protected function titleInput()
	{
		return !$this->model->id ? parent::titleInput() : parent::titleInput()->onChange;
	}

	public function taskInfoElements()
	{
		return _Rows(
			parent::taskInfoElements(),
			$this->model->id ? null : _SubmitButton()->class('mx-4')->browse($this->taskRelatedLists()),
		);
	}

	protected function rightColumn()
	{
		return $this->model->id ?

        	new TaskDetailsList([
        		'task_id' => $this->model->id,
        		'task_card_id' => TasksCard::ID,
        	]) :

        	_Html('tasks.add-action-first')
				->class('my-4 mx-auto w-48 text-level1 text-center');
	}

	protected function contactView()
	{
		return false;
		// return auth()->user()->isContact();
	}

	protected function singleColumn()
	{
		return $this->contactView();
	}

	protected function createTaskAndDetailFromThread($thread)
	{
		$task = new Task();
		$task->team_id = currentTeamId();
		$task->title = $thread->subject;
		$task->from_thread_id = $thread->id;
		$task->save();

		$taskDetail = new TaskDetail();
		$taskDetail->user_id = auth()->id();
		$taskDetail->details = '<p><a href="'.$thread->getPreviewRoute().'" target="_blank">'.__('tasks.task-created-from-email').': '.$thread->subject.'</a></p>';
		$task->taskDetails()->save($taskDetail);

		return $task;
	}
}
