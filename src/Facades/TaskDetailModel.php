<?php

namespace Kompo\Tasks\Facades;

use Illuminate\Support\Facades\Facade;
use Kompo\Auth\Facades\FacadeUtils;

/**
 * @mixin \Kompo\Tasks\Models\TaskDetail
 */
class TaskDetailModel extends Facade
{
    use FacadeUtils;

    protected static function getFacadeAccessor()
    {
        return 'task-detail-model';
    }
}