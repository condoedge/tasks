<?php

namespace Kompo\Tasks\Facades;

use Illuminate\Support\Facades\Facade;
use Kompo\Auth\Facades\FacadeUtils;

/**
 * @mixin \Kompo\Tasks\Models\Task
 */
class TaskModel extends Facade
{
    use FacadeUtils;

    protected static function getFacadeAccessor()
    {
        return 'task-model';
    }
}