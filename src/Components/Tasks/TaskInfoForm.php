<?php

namespace Kompo\Tasks\Components\Tasks;

use Condoedge\Utils\Kompo\Common\Form;
use Kompo\Auth\Facades\TeamModel;
use Kompo\Tasks\Components\Tasks\Concerns\HandlesTaskAssignables;
use Kompo\Tasks\Facades\TaskModel;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;
use Kompo\Tasks\Models\TaskAssignableRegistry;

abstract class TaskInfoForm extends Form
{
	use HandlesTaskAssignables;

	protected $subtitle = 'TASK';

	protected $threadId;
	protected $tagIds;

	protected $assignedCol = 'col-md-7';

	public $model = TaskModel::class;

	public function beforeSave()
	{
		if (request()->has('status')) {
			$this->model->handleStatusChange(request('status'));
		}

		$this->fillAssignmentBeforeSave();
	}

	public function afterSave()
	{
		$this->syncTaskAssignationsFromRequest();
	}

	public function taskInfoElements()
	{
		return _Rows(
			_Rows(
				_Rows(
					_MiniTitle('tasks.task')->class('mt-4'),

					$this->submitsRefresh(
						$this->titleInput()
					),

					$this->submitsRefresh(
						$this->statusInput()
					),
				)->class('card-gray-100 px-6 mx-4 !space-y-2 pb-5'),

				$this->takeAssignationCard(),

				_Rows(
					_MiniTitle('tasks.assigned-to')->class('mt-4'),

					_Panel(
						$this->assignmentPanel()
					)->id('task-assignment-panel'),

					$this->submitsRefresh(
						_TagsMultiSelect()
							->class('tags-select')
							->default($this->tagIds),
					),

					$this->visibilityAndOptions(),
				)->class('card-gray-100 px-6 mx-4 !space-y-2 pb-5'),


				$this->taskLinksCard()
			),
		)->id('task-info-elements');
	}

	protected function panelWrapper($title, $icon, $col1, $col2 = null)
	{
		return _Rows(
			_Columns(
				_FlexBetween(
					_PageTitle('tasks.task')
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

	public function assignItToMyself()
	{
		if (!$this->authUserCanTakeAssignation()) {
			abort(403, __('kompo.unauthorized-action'));
		}

		$this->model->takeOwnership(auth()->id());
	}

	public function searchTeamChildren()
	{
		return TeamModel::parseOptions(
			TeamModel::active()->validForTasks()->whereIn('id', currentTeam()->getAllChildrenRawSolution())->get()
		);
	}

    public function retrieveTeamChildren($id)
    {
        $team = TeamModel::findOrFail($id);

        return TeamModel::parseOptions(
			collect([$team])
		)->toArray();
	}

	protected function titleInput()
	{
		return _Translatable()->placeholder('tasks.title')->name('title')
			->class('[&>.vlInputWrapper>.vlLocales]:hidden'); // Hide locales selector for now
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

			$this->model->id ? $this->submitsRefresh(
				_Checkbox('tasks.priority')->class('[&>label>.icon-spinner]:hidden')->name('urgent')
			) : null,
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
			'team_id' => 'required|exists:teams,id',
			'visibility' => 'required|in:' . collect(TaskVisibilityEnum::cases())->pluck('value')->join(','),
			'assignment_type' => 'required|in:' . TaskAssignableRegistry::configs()->keys()->join(','),
			'task_assignable_ids' => 'required',
			'urgent' => 'boolean',
		] + $this->assignableValidationRules();
	}
}
