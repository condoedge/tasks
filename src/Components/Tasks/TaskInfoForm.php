<?php

namespace Kompo\Tasks\Tasks;

use Kompo\Form;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;
use Kompo\Tasks\Models\Task;

abstract class TaskInfoForm extends Form
{
	public $model = Task::class;

	protected $subtitle = 'TASK';

	protected $threadId;
	protected $tagIds;

	protected $assignedCol = 'col-md-7';

	public function beforeSave()
	{
		$this->model->public = $this->model->visibility ?: TaskVisibilityEnum::MANAGERS;

		$this->model->handleStatusChange(request('status'));
	}

	public function taskInfoElements()
	{
		return _Rows(
	        _Rows(
	        	_FlexBetween(
	        		_MiniTitle('task.task'),
	        		$this->taskDeleteLink()
				)->class('mt-4'),

				$this->submitsRefresh(
					$this->titleInput()
	        	),

				$this->submitsRefresh(
					$this->statusInput()
				),
			)->class('card-gray-100 px-6 mx-4 !space-y-2 pb-5'),

	        _Rows(
				_MiniTitle('assigned_to')->class('mt-4'),
				$this->submitsRefresh(
					_Select()->placeholder('task.task-lead')->name('assigned_to')
						->options(
							currentTeam()->nonContactUsers()->pluck('name', 'id')
						)
						->icon(_Sax('profile'))
						->default(auth()->user()->id)
				),
				$this->submitsRefresh(
					_TagsMultiSelect()
						->class('tags-select')
						->default($this->tagIds)
				),

					$this->visibilityAndOptions(),
			)->class('card-gray-100 px-6 mx-4 !space-y-2 pb-5'),


	        $this->taskLinksCard()
		);
	}

	protected function panelWrapper($title, $icon, $col1, $col2 = null)
	{
		return _Rows(
			_PageTitle($title)
				->icon(
					_Svg($icon)->class('text-5xl')
				)
				->class('p-4 py-2 md:py-4 bg-white'),
			_Rows(
				_Columns(
					!$col1 ? null : $col1
						->class('h-full')
						->style('min-width:250px'),
					!$col2 ? null : $col2
						->class('h-full border-gray-200')
						->style('min-width:300px')
				)->class('h-full')
				->noGutters()
			)->class('h-full')
		)->class('overflow-auto mini-scroll h-screen');
	}

	protected function titleInput()
	{
		return _Input()->placeholder('Title')->name('title');
	}

	protected function statusInput()
	{
		return _Select()->placeholder('Status')->name('status')
			->options(Task::statuses())
			->default((string) TaskStatusEnum::OPEN);
	}

	protected function visibilityAndOptions()
	{
		return _Rows(
			$this->submitsRefresh(
				_Select()->name('Visibility')
                ->name('public')
                ->icon(_Sax('eye'))
                ->options(Task::visibilities())
                ->default((string) TaskVisibilityEnum::MANAGERS)
			),

			$this->model->id ? $this->submitsRefresh(_Checkbox('Priority')->name('urgent')) : null,
		);
	}

	protected function taskLinksCard()
	{
		return !$this->model->id ? null : _Rows(
			new TaskLinksCard(['task_id' => $this->model->id])
		)->class('card-gray-100 px-6 py-4 mx-4');
	}

	protected function submitsRefresh($komponent)
	{
		return !$this->model->id ? $komponent : $komponent->submit()->browse($this->taskRelatedLists());
	}

	protected function taskRelatedLists()
	{
		return array_merge(Task::taskListsToRefresh(), [
			$this->cardIdToRefresh,
		]);
	}

	protected function taskDeleteLink()
	{
		if(!auth()->user()->can('delete', $this->model))
			return;

		return _DeleteLink()->byKey($this->model)->class('text-gray-500')
			->closeSlidingPanel()
			->browse($this->taskRelatedLists());
	}

	public function rules()
	{
		return [
			'title' => 'required|max:255',
			'status' => 'required',
		];
	}
}
