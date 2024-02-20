<?php

namespace Kompo\Tasks\Models;

use Kompo\Auth\Models\Files\File;
use Kompo\Auth\Models\Model;
use Kompo\Auth\Models\Traits\BelongsToUserTrait;
use Kompo\Auth\Models\Traits\MorphManyFilesTrait;

class TaskDetail extends Model
{
    use BelongsToUserTrait,
        MorphManyFilesTrait;

    protected $casts = [
        'reminder_at' => 'datetime'
    ];

    protected $appends = [
        'event_class'
    ];


    /* RELATIONSHIPS */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function reads()
    {
        return $this->hasMany(TaskRead::class);
    }

    public function read()
    {
        return $this->hasOne(TaskRead::class)->where('user_id', auth()->user()->id);
    }

    /* SCOPES */
    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->withReminder()->whereNotNull('completed_at');
    }

    public function scopeWithReminder($query)
    {
        return $query->whereNotNull('reminder_at');
    }

    /* ACTIONS */
    public function complete()
    {
        $this->completed_at = now();

        $this->save();
    }

    public function reset()
    {
        $this->completed_at = null;

        $this->save();
    }

    public function markAsRead()
    {
        if ($this->read){
            return;
        }

        $mr = new TaskRead();
        $mr->setUserId();
        $mr->task_detail_id = $this->id;
        $mr->read_at = now();
        $mr->save();
    }

    public function delete()
    {
        $this->deleteFiles();

        parent::delete();
    }

    public function addLinkedFiles($linkedFileIds = [])
    {
        collect($linkedFileIds)->each(function($fileId){
            $file = File::find($fileId);
            if (!$this->filesFromRelations()->pluck('id')->contains($file->id)) {
                $file->linkToOrAssociate($this->id, 'task-detail');
            }
        });

        $this->load('files');
    }

    /* ATTRIBUTES */
    public function getEventClassAttribute()
    {
        return 'bg-level3';
    }

    public function getBorderClassAttribute()
    {
        return 'border-level3';
    }

    /* CALCULATED FIELDS */


    /* QUERIES */
    public static function queryBetween($startDate, $endDate)
    {
        return static::select()->selectRaw("reminder_at as start_date")
            ->with('task')
            ->whereHas('task', fn($q) => $q->userVisibility())
            ->whereBetween('reminder_at', [$startDate, $endDate])
            ->whereNotNull('reminder_at')
            ->incomplete()
            ->orderBy('reminder_at');

    }

    /* ELEMENTS */
    public function calendarLeftBox()
    {
        return _Rows(
            _Html('Reminder'),
            _Html(
                $this->reminder_at->format('H:i') != '00:00' ?

                    $this->reminder_at->format('H:i') :

                    'all_day'

            )
        );
    }

    public function calendarMiddleBox($withUnion = false)
    {
        return _Rows(
            _Html($this->task->title)->class('text-sm'),
            _Html($this->task->status_label)->class('text-xs text-gray-300'),
            (($union = $this->task->union) && $withUnion)?
                _UnionUnitBadge($union->display)->class('mt-2') : null
        );
    }

    public function calendarRightBox()
    {
        return;
    }

}
