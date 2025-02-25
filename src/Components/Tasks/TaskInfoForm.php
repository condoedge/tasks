<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Form;
use Kompo\Tasks\Facades\TaskModel;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;

use Kompo\Auth\Facades\TeamModel;

abstract class TaskInfoForm extends Form
{
	protected $subtitle = 'TASK';

	protected $threadId;
	protected $tagIds;

	protected $assignedCol = 'col-md-7';

	public function created()
	{
		$this->model(TaskModel::getClass());
	}

	public function beforeSave()
	{
		$this->model->handleStatusChange(request('status'));
	}

	public function taskInfoElements()
	{
		return _Rows(
	        _Rows(
				_MiniTitle('tasks.task')->class('mt-4'),

				$this->submitsRefresh(
					$this->titleInput()
	        	),

				$this->submitsRefresh(
					$this->statusInput()
				),
			)->class('card-gray-100 px-6 mx-4 !space-y-2 pb-5'),

	        _Rows(
				_MiniTitle('tasks.assigned-to')->class('mt-4'),
				$this->submitsRefresh(
					_Select()->placeholder('tasks.team-assigment')->name('team_id')
						->searchOptions(0, 'searchTeamChildren')
						->default(currentTeamId())
				),
				$this->submitsRefresh(
					_Select()->placeholder('tasks.task-lead')->name('assigned_to')
						->options(
							currentTeam()->assignToOptions(),
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
			_Columns(
				_FlexBetween(
					_PageTitle($title)
						->icon(
							_Svg($icon)->class('text-5xl')
						),
					$this->taskDeleteLink(),
				)->class('p-4 py-2 md:py-4 bg-white items-center')->col('col-md-5')
			),
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

	public function searchTeamChildren()
	{
		return TeamModel::parseOptions(
			TeamModel::active()->validForTasks()->whereIn('id', currentTeam()->getAllChildrenRawSolution())->get()
		);
	}

	protected function titleInput()
	{
		return _Translatable()->placeholder('tasks.title')->name('title');
	}

	protected function statusInput()
	{
		return _Select()->placeholder('tasks.status')->name('status')
			->options(TaskStatusEnum::optionsWithLabels())
			->default(TaskStatusEnum::OPEN);
	}

	protected function visibilityAndOptions()
	{
		return _Rows(
			$this->submitsRefresh(
				_Select()
                ->name('visibility')
                ->icon(_Sax('eye'))
                ->options(TaskVisibilityEnum::optionsWithLabels())
                ->default(TaskVisibilityEnum::ALL)
			),

			$this->model->id ? $this->submitsRefresh(_Checkbox('tasks.priority')->name('urgent')) : null,
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
		return array_merge(TaskModel::taskListsToRefresh(), [
			TasksCard::ID,
		]);
	}

	protected function taskDeleteLink()
	{
		if(!auth()->user()->can('delete', $this->model))
			return;

		return _Delete($this->model)->class('text-gray-500 hover:text-danger')
			->closeSlidingPanel()
			->browse($this->taskRelatedLists());
	}

	public function rules()
	{
		return [
			'title' => 'required|max:255',
			'status' => 'required',
			'team_id' => 'required',
		];
	}
}
