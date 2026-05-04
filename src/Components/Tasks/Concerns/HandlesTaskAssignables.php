<?php

namespace Kompo\Tasks\Components\Tasks\Concerns;

use Kompo\Tasks\Models\TaskAssignableRegistry;

/**
 * Form-side concerns for the polymorphic task-assignable system.
 * Expects the consuming class to expose $this->model (a Task instance).
 */
trait HandlesTaskAssignables
{
    public function assignmentPanel()
    {
        return _Rows(
            $this->teamInput(),
            $this->assignmentTypeInput(),
            $this->assignableInputs(),
        )->class('!space-y-2');
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
        return $this->model->canBeTakenBy(auth()->id());
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
        $configs = TaskAssignableRegistry::configs();
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
            TaskAssignableRegistry::configs()
                ->map(fn($config, $type) => $this->assignableInput($type))
                ->values()
                ->all(),
        );
    }

    protected function assignableInput($type)
    {
        $config = TaskAssignableRegistry::config($type);
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

    protected function fillAssignmentBeforeSave()
    {
        if (!$this->shouldSyncAssignmentFromRequest()) {
            return;
        }

        $config = TaskAssignableRegistry::config($this->selectedAssignmentType());
        $this->model->applyAssignment($config['model'], $this->selectedAssignableIdsFromRequest());
    }

    protected function syncTaskAssignationsFromRequest()
    {
        if (!$this->shouldSyncAssignmentFromRequest() || !$this->model->id) {
            return;
        }

        $config = TaskAssignableRegistry::config($this->selectedAssignmentType());
        $this->model->applyAssignment($config['model'], $this->selectedAssignableIdsFromRequest());
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
        $configs = TaskAssignableRegistry::configs();
        $requestType = request('assignment_type');

        if ($requestType && $configs->has($requestType)) {
            return $requestType;
        }

        if ($this->model->assigned_to && ($userType = TaskAssignableRegistry::typeForClass(\Kompo\Auth\Facades\UserModel::getClass()))) {
            return $userType;
        }

        $assignation = $this->model->taskAssignations()->first();

        if ($assignation && ($type = TaskAssignableRegistry::typeForClass(TaskAssignableRegistry::classFromAssignation($assignation)))) {
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

        $config = TaskAssignableRegistry::config($type);

        if (TaskAssignableRegistry::isUserClass($config['model']) && $this->model->assigned_to) {
            return [$this->model->assigned_to];
        }

        return $this->model->taskAssignations()
            ->get()
            ->filter(fn($assignation) => TaskAssignableRegistry::assignationMatchesClass($assignation, $config['model']))
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

    protected function taskAssignableOptions($type)
    {
        $config = TaskAssignableRegistry::config($type);
        $class = $config['model'];
        $query = $class::query();
        $model = new $class();
        $keyName = TaskAssignableRegistry::keyNameFor($class);

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

    protected function assignableValidationRules()
    {
        $config = TaskAssignableRegistry::config($this->selectedAssignmentType());
        $model = new $config['model']();
        $existsRule = 'exists:' . $model->getTable() . ',' . TaskAssignableRegistry::keyNameFor($config['model']);

        return is_array(request('task_assignable_ids')) ? [
            'task_assignable_ids' => 'required|array|min:1',
            'task_assignable_ids.*' => $existsRule,
        ] : [
            'task_assignable_ids' => 'required|' . $existsRule,
        ];
    }
}
