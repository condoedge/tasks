<?php

namespace Kompo\Tasks\Components\Tasks;

use Condoedge\Utils\Kompo\Common\Form;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kompo\Auth\Facades\TeamModel;
use Kompo\Auth\Facades\UserModel;
use Kompo\Tasks\Facades\TaskModel;
use Kompo\Tasks\Models\TaskAssignation;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;

abstract class TaskInfoForm extends Form
{
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

		$this->model->assigned_to = auth()->id();
		$this->model->save();
		$this->model->taskAssignations()->delete();
		$this->model->unsetRelation('taskAssignations');
	}

	public function assignmentPanel()
	{
		return _Rows(
			$this->teamInput(),
			$this->assignmentTypeInput(),
			$this->assignableInputs(),
		)->class('!space-y-2');
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

	protected function takeAssignationCard()
	{
		if (!$this->authUserCanTakeAssignation()) {
			return null;
		}

		return _CardGray100(
			_Html('tasks.position-assigned-task')->class('font-semibold'),
			_Html('tasks.position-assigned-task-help')->class('text-sm text-gray-600'),
			_Button('tasks.take-ownership-of-this-task')->class('w-full mt-3')
				->onClick(fn($e) => $e->run('() => setLoadingInPanel("task-info-elements")')
					&& $e->selfPost('assignItToMyself')
						->alert('tasks.task-taken-successfully')
						->refresh()
				),
		)->class('card-gray-100 p-4 mx-10 !space-y-2 mb-5');
	}

	protected function authUserCanTakeAssignation()
	{
		return $this->model->id
			&& !$this->model->assigned_to
			&& $this->hasNonUserAssignation()
			&& collect($this->model->getAllUserAssignations())->where('id', auth()->id())->isNotEmpty();
	}

	protected function hasNonUserAssignation()
	{
		return $this->model->taskAssignations()
			->get()
			->contains(fn($assignation) => !$this->isUserAssignableClass($this->assignationClass($assignation)));
	}

	protected function teamInput()
	{
		return $this->submitsRefresh(
			_Select()->placeholder('tasks.team-assigment')->name('team_id')
				->searchOptions(0, 'searchTeamChildren', 'retrieveTeamChildren')
				->default($this->selectedTeamId())
		);
	}

	protected function assignmentTypeInput()
	{
		$configs = $this->taskAssignableConfigs();
		$type = $this->selectedAssignmentType();

		if ($configs->count() <= 1) {
			return _Hidden()->name('assignment_type', false)->value($type);
		}

		return _ButtonGroup('tasks.assignment-type')->name('assignment_type', false)
			->optionClass('px-4 py-2 text-center cursor-pointer')
			->selectedClass('bg-level1 text-white font-medium', 'bg-gray-200 text-level1 font-medium')
			->options($configs->mapWithKeys(fn($config, $key) => [$key => __($config['label'])])->toArray())
			->value($type);
	}

	protected function assignableInputs()
	{
		return _Rows(
			$this->taskAssignableConfigs()
				->map(fn($config, $type) => $this->assignableInput($type))
				->values()
				->all(),
		);
	}

	protected function assignableInput($type)
	{
		$config = $this->taskAssignableConfig($type);
		$multiple = $config['multiple'] ?? false;
		$field = $multiple ? _MultiSelect($config['label']) : _Select($config['label']);
		$values = $this->selectedAssignableIdsForType($type);

		return $this->submitsRefresh(
			$field->name('task_assignable_ids', false)
				->placeholder($config['placeholder'] ?? $config['label'])
				->options($this->taskAssignableOptions($type))
				->icon(_Sax($config['icon'] ?? 'profile'))
				->value($multiple ? $values : ($values[0] ?? null))
				->jsEnableWhen('assignment_type', $type)
		);
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

	protected function fillAssignmentBeforeSave()
	{
		if (!$this->shouldSyncAssignmentFromRequest()) {
			return;
		}

		$config = $this->taskAssignableConfig($this->selectedAssignmentType());
		$ids = $this->selectedAssignableIdsFromRequest();

		$this->model->assigned_to = $this->isUserAssignableClass($config['model']) && $ids->count() === 1
			? $ids->first()
			: null;
	}

	protected function syncTaskAssignationsFromRequest()
	{
		if (!$this->shouldSyncAssignmentFromRequest() || !$this->model->id) {
			return;
		}

		$config = $this->taskAssignableConfig($this->selectedAssignmentType());
		$ids = $this->selectedAssignableIdsFromRequest();

		$this->model->taskAssignations()->delete();
		$this->model->unsetRelation('taskAssignations');

		if ($this->isUserAssignableClass($config['model']) && $ids->count() === 1) {
			return;
		}

		TaskAssignation::createForMany(
			$this->model->id,
			$this->assignableModels($config['model'], $ids),
		);

		$this->model->unsetRelation('taskAssignations');
	}

	protected function shouldSyncAssignmentFromRequest()
	{
		return request()->has('assignment_type') || request()->has('task_assignable_ids') || request()->has('assigned_to');
	}

	protected function selectedTeamId()
	{
		return request('team_id') ?: $this->model->team_id ?: currentTeamId();
	}

	protected function selectedAssignmentType()
	{
		$configs = $this->taskAssignableConfigs();
		$requestType = request('assignment_type');

		if ($requestType && $configs->has($requestType)) {
			return $requestType;
		}

		if ($this->model->assigned_to && ($userType = $this->assignmentTypeForClass(UserModel::getClass()))) {
			return $userType;
		}

		$assignation = $this->model->taskAssignations()->first();

		if ($assignation && ($type = $this->assignmentTypeForClass($this->assignationClass($assignation)))) {
			return $type;
		}

		return $configs->keys()->first();
	}

	protected function selectedAssignableIdsForType($type)
	{
		if (request()->has('task_assignable_ids') || request()->has('assigned_to')) {
			if (request()->has('assignment_type') && request('assignment_type') !== $type) {
				return [];
			}

			return $this->selectedAssignableIdsFromRequest()->all();
		}

		$config = $this->taskAssignableConfig($type);

		if ($this->isUserAssignableClass($config['model']) && $this->model->assigned_to) {
			return [$this->model->assigned_to];
		}

		return $this->model->taskAssignations()
			->get()
			->filter(fn($assignation) => $this->assignationMatchesClass($assignation, $config['model']))
			->pluck('assignable_id')
			->values()
			->all();
	}

	protected function selectedAssignableIdsFromRequest()
	{
		$ids = request('task_assignable_ids', request('assigned_to'));

		return collect(is_array($ids) ? $ids : [$ids])
			->filter(fn($id) => $id !== null && $id !== '')
			->values();
	}

	protected function taskAssignableConfigs()
	{
		$configs = config('kompo-tasks.assignables') ?: [
			'person' => [
				'model' => UserModel::getClass(),
				'label' => 'tasks.person',
				'multiple' => true,
				'icon' => 'profile',
			],
		];

		return collect($configs)
			->mapWithKeys(function($config, $key) {
				$normalized = $this->normalizeAssignableConfig($config, $key);

				return $normalized ? [$normalized['key'] => $normalized] : [];
			});
	}

	protected function normalizeAssignableConfig($config, $key)
	{
		$class = is_string($config) ? $config : ($config['model'] ?? $config['class'] ?? null);

		if (!$class || !class_exists($class)) {
			return null;
		}

		$key = is_string($key) ? $key : $this->assignableKeyFromClass($class);

		return [
			'key' => $key,
			'model' => $class,
			'label' => is_array($config) ? ($config['label'] ?? 'tasks.'.$key) : 'tasks.'.$key,
			'placeholder' => is_array($config) ? ($config['placeholder'] ?? null) : null,
			'multiple' => is_array($config) ? ($config['multiple'] ?? $this->isUserAssignableClass($class)) : $this->isUserAssignableClass($class),
			'icon' => is_array($config) ? ($config['icon'] ?? null) : null,
		];
	}

	protected function taskAssignableConfig($type)
	{
		return $this->taskAssignableConfigs()->get($type) ?: $this->taskAssignableConfigs()->first();
	}

	protected function taskAssignableOptions($type)
	{
		$config = $this->taskAssignableConfig($type);
		$class = $config['model'];
		$query = $class::query();
		$model = new $class();
		$keyName = $this->taskAssignableKeyName($model);

		if (method_exists($model, 'scopeValidForTaskAssignment')) {
			$query->validForTaskAssignment($this->selectedTeamId());
		} elseif (method_exists($model, 'teamRoles')) {
			$query->whereHas('teamRoles', fn($q) => $q->where('team_id', $this->selectedTeamId()));
		}

		$assignables = $query->take(200)->get();
		$selectedIds = collect($this->selectedAssignableIdsForType($type))
			->filter(fn($id) => $id !== null && $id !== '')
			->values();

		if ($selectedIds->isNotEmpty()) {
			$missingSelectedIds = $selectedIds
				->reject(fn($id) => $assignables->contains(fn($assignable) => (string) $assignable->getIdForTask() === (string) $id))
				->values();

			if ($missingSelectedIds->isNotEmpty()) {
				$assignables = $assignables
					->concat($class::query()->whereIn($keyName, $missingSelectedIds->all())->get())
					->unique(fn($assignable) => (string) $assignable->getIdForTask())
					->values();
			}
		}

		return $assignables
			->mapWithKeys(fn($assignable) => [$assignable->getIdForTask() => $assignable->display ?? $assignable->name ?? $assignable->getKey()])
			->toArray();
	}

	protected function assignableModels($class, $ids)
	{
		$model = new $class();

		return $class::query()
			->whereIn($this->taskAssignableKeyName($model), $ids->all())
			->get()
			->all();
	}

	protected function assignmentTypeForClass($class)
	{
		return $this->taskAssignableConfigs()
			->filter(fn($config) => $this->classesMatch($config['model'], $class))
			->keys()
			->first();
	}

	protected function assignationMatchesClass($assignation, $class)
	{
		return $this->classesMatch($this->assignationClass($assignation), $class);
	}

	protected function assignationClass($assignation)
	{
		return Relation::getMorphedModel($assignation->assignable_type) ?: $assignation->assignable_type;
	}

	protected function classesMatch($classA, $classB)
	{
		return $classA === $classB || is_a($classA, $classB, true) || is_a($classB, $classA, true);
	}

	protected function assignableKeyFromClass($class)
	{
		return $this->isUserAssignableClass($class) ? 'person' : strtolower(class_basename($class));
	}

	protected function isUserAssignableClass($class)
	{
		return $this->classesMatch($class, UserModel::getClass());
	}

	protected function taskAssignableKeyName($assignable)
	{
		return TaskAssignation::taskAssignableKeyName($assignable);
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
			'assignment_type' => 'required|in:' . $this->taskAssignableConfigs()->keys()->join(','),
			'task_assignable_ids' => 'required',
			'urgent' => 'boolean',
		] + $this->assignableValidationRules();
	}

	protected function assignableValidationRules()
	{
		$config = $this->taskAssignableConfig($this->selectedAssignmentType());
		$model = new $config['model']();
		$existsRule = 'exists:' . $model->getTable() . ',' . $this->taskAssignableKeyName($model);

		return is_array(request('task_assignable_ids')) ? [
			'task_assignable_ids' => 'required|array|min:1',
			'task_assignable_ids.*' => $existsRule,
		] : [
			'task_assignable_ids' => 'required|' . $existsRule,
		];
	}
}
