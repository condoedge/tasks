<?php

namespace Kompo\Tasks\Components\Tasks;

use Kompo\Query;
use Kompo\Tasks\Models\Task;

abstract class TasksMainView extends Query
{
	public $containerClass = 'container-fluid';

    protected $defaultUrgency;
    protected $defaultAssignedTo;

	public function query()
	{
        $query= Task::baseQuery()->forTeam();

        if (request('only_mine') || request('mine_urgent')) {
            $query = $query->where('assigned_to', auth()->id());
            $this->defaultAssignedTo = auth()->id();
        }

        if (request('urgent') || request('mine_urgent')) {
            $query = $query->where('urgent', 1);
            $this->defaultUrgency = 1;
        }

        return $query;
	}

	public function top()
	{
		return $this->topFilterHeader('Tasks',
            _Columns(
                _Input('Title')->name('title')->filter(),
                _MultiSelect('task.assigned-to')->name('assigned_to')->options(
                    currentTeam()->users()->pluck('users.name', 'users.id')
                )->filter(),
                _TagsMultiSelect('Tags')->filter()
            ),
            !auth()->user()->can('create', Task::class) ? null : _FlexEnd(
                _Link('task.add_task')->icon('icon-plus')->button()->get('task.form')->inDrawer()
            )->class('mt-2 sm:mt-0'),
            null,
            _Flex(
                $this->selectLinkFilter(auth()->user()->id, 'profile-circle', 'task.show-my-tasks-only')
                    ->name('only_mine', false)->default($this->defaultAssignedTo),
                $this->selectLinkFilter(1, 'info-circle', 'Priority')
                    ->name('urgent', false)->default($this->defaultUrgency),
                $this->selectLinkFilter(365, 'tick-circle', 'task.with-closed-tasks')
                    ->name('closed_since', false),
            )
        )->class('flex-wrap');
	}

    protected function topFilterHeader($title, $filters, $rightFilters = null, $bottomFilters = null, $leftFilters = null)
	{
        $filtersId = 'filters-'.uniqid();

		return _Rows(
            _FlexBetween(
                _Flex(
                    _PageTitle($title)->class('mr-4 mb-4 md:mb-0 w-full md:w-auto'),
                    _Flex(
                        _FilterToggler($filtersId),
                        $leftFilters,
                    )->class('flex-wrap'),
                )->class('flex-wrap'),
                _FlexEnd(
                    $rightFilters,
//                    $this->viewSwitcher(),
                )
            )->class('mb-4 flex-wrap'),
            $bottomFilters,
            $filters->id($filtersId)->class('mt-2'),
        );
	}

    protected function viewSwitcher()
    {
        return !$this->switchToRouteName ? null : _Link()
            ->icon(
                _Svg($this->viewIcon)->class('text-2xl')
            )
            ->href($this->switchToRouteName)
            ->class('text-level3 px-4 py-2');
    }

    protected function selectLinkFilter($value, $icon, $balloonLabel, $balloonPos = 'down')
    {
        return _HtmlField()->selectedValue($value)
            ->icon(_Sax($icon,20))->balloon($balloonLabel, $balloonPos)
            ->class('ml-2 p-2 text-xl leading-3 rounded-full border border-level1 cursor-pointer')
            ->selectedClass('bg-info text-white')
            ->filter();
    }
}
