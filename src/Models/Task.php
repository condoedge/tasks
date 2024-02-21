<?php

namespace Kompo\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kompo\Auth\Models\Model;
use Kompo\Auth\Models\Teams\BelongsToTeamTrait;
use Kompo\Tasks\Models\Enums\TaskStatusEnum;
use Kompo\Tasks\Models\Enums\TaskVisibilityEnum;
use Kompo\Auth\Models\Tags\MorphToManyTagsTrait;
use Kompo\Database\HasTranslations;

class Task extends Model
{
    use BelongsToTeamTrait, MorphToManyTagsTrait, HasTranslations;

    protected $casts = [
        'status' => TaskStatusEnum::class,
        'visibility' => TaskVisibilityEnum::class,
        'closed_at' => 'datetime',
    ];

    protected $translatable = [
        'title',
    ];

    
    public function taskDetails(): HasMany
    {
        return $this->hasMany(TaskDetail::class);
    }

    public function incompleteTaskDetails(): HasMany
    {
        return $this->taskDetails()->incomplete();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function taskLinks(): HasMany
    {
        return $this->hasMany(TaskLink::class);
    }

    /* CALCULATED FIELDS */
    public function isClosed()
    {
        return $this->status == TaskStatusEnum::CLOSED;
    }

    public function progressPct()
    {
        if($this->isClosed())
            return 1;

        $totalSteps = $this->taskDetails()->withReminder()->count();

        if($totalSteps == 0)
            return 0;

        return $this->taskDetails()->completed()->count() / $totalSteps;
    }

    /* ACTIONS */
    public function close()
    {
        $this->status = TaskStatusEnum::CLOSED;
        $this->closed_by = auth()->user()->id;
        $this->closed_at = now();
        $this->taskDetails->each->complete();
        return $this->save();
    }

    public function handleStatusChange($status)
    {
        $this->status = $status;

        return $status == TaskStatusEnum::CLOSED ? $this->close() : $this->save();
    }

    /* SCOPES */
    public function scopeMine($query)
    {
        return $query->where('assigned_to', auth()->user()->id)
            ->orWhere(function($q){
                $q->whereNull('assigned_to')
                    ->where('added_by', auth()->user()->id);
            });
    }

    public function scopeOpen($query)
    {
        return $query->where('status', TaskStatusEnum::OPEN);
    }

    public function scopePending($query)
    {
        return $query->where('status', TaskStatusEnum::PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', TaskStatusEnum::PROCESSING);
    }

    public function scopeNotClosed($query)
    {
        return $query->where('status', '<>', TaskStatusEnum::CLOSED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', TaskStatusEnum::CLOSED);
    }

    public function scopeUserVisibility($query)
    {
        // if(auth()->user()->isContact()) {

        //     if (auth()->user()->isBoardMember()) {
        //         return $query->whereIn('visibility', [TaskVisibilityEnum::ALL, TaskVisibilityEnum::BOARD]);
        //     }

            return $query->where('visibility', TaskVisibilityEnum::ALL);
        // }

        // return $query;
    }

    public function scopeWithReminderInfo($query)
    {
        return $query->withMin('incompleteTaskDetails', 'reminder_at')
            ->orderByRaw("IFNULL(incomplete_task_details_min_reminder_at, '2100-01-01')");
    }

    /* QUERIES */
    public static function baseQuery()
    {
        return static::with('assignedTo', 'taskDetails.read')
            ->withCount('taskDetails')
            // ->withCount('unreadNotifications')
            ->withReminderInfo()
            ->orderByDesc('id') //Had to add a unique ordering column otherwise paginate would bug
            ->userVisibility();
    }

    /* ELEMENTS */
    public function taskCard()
    {
        $minReminderDate = $this->incomplete_task_details_min_reminder_at;

        $taskRead = $this->task_details_count === $this->taskDetails->filter(fn($td) => $td->read)->count();

        $taskNotified = $this->unread_notifications_count ?: null;

        return _Rows(
            _FlexBetween(
                !$minReminderDate ? null :
                    _Html(
                        $minReminderDate->format('d M y')
                        //.' ('.$minReminderDate->diffForHumans().')'
                    )->class('text-gray-500 text-xs whitespace-nowrap'),
            ),
            _Html(
                ($taskNotified ? '<span class="unreadPill bg-danger"></span> ' : ($taskRead ? '' : '<span class="unreadPill bg-info"></span> ')).
                $this->title
            )->class('truncate text-sm mt-2')->class('task-pill'),
            _FlexBetween(
                _UserImgDate(
                    $this->assignedTo ?: $this->createdBy,
                    $this->created_at
                ),
                _FlexEnd(
                    _ChatCount($this->task_details_count)
                        ->class('flex items-center mr-2'),
                    $this->taskDropdown(),
                )->class('text-gray-600 text-xs mt-2'),
            )->class('mt-2')
        )->class('p-4 cursor-pointer')
        ->class($taskRead ? 'task-read' : '');
    }

    public function taskDropdown()
    {
        return _TripleDotsDropdown(
            _Rows(
                _Html($this->status_label)->icon('icon-question-circle'),
                $this->urgent ? _Html('Priority')->icon(_Sax('info-circle')) : null,
                $this->public ? _Html('publicly_visible')->icon('speakerphone') : null
            )->class('w-48 text-level3 border border-gray-300 rounded-xl p-6')
        );
    }

    public static function taskListsToRefresh()
    {
        return [
            'calendar-main-view',
            'tasks.kanban',
            'tasks.manager',
            'dashboard-agenda-card',
            'calendar-union-view',
        ];
    }

    /* ACTIONS */
    public function markRead()
    {
        \DB::transaction(function(){
            $this->taskDetails()->with('read')->get()->each(fn($td) => $td->markRead());
        });
    }

    // public function notify($userId)
    // {
    //     if(Notification::userHasUnseenNotifications($userId, [$this->id], 'task'))
    //         return;

    //     Notification::notify($this, $userId);
    // }

    public function delete()
    {
        $this->taskDetails->each->delete();
        $this->taskLinks->each->delete();
        // $this->deleteNotifications();

        parent::delete();
    }
}
