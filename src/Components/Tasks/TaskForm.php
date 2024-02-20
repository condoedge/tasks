<?php

namespace Kompo\Tasks\Components\Tasks;

class TaskForm extends TaskInfoForm
{
    protected $slug = 'tasks';

	protected $refresh = true;

	protected $cardIdToRefresh;

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

		$this->cardIdToRefresh = $this->refreshId();

		$this->model->markRead();

        if($this->model->notifications()->forAuthUser()->count())
            $this->model->notifications()->forAuthUser()->get()->each->markSeen();

		if($this->model->id)
			$this->_kompo('options', [
				'refresh' => false
			]);
	}

	public function render()
	{
		return $this->panelWrapper($this->model->title ?: 'task.add_task', 'annotation',

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
        		'task_card_id' => $this->cardIdToRefresh
        	]) :

        	_Html('task.add-action-first')
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
}
