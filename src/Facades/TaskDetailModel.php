<?php

namespace Kompo\Tasks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Kompo\Tasks\Models\TaskDetail
 */
class TaskDetailModel extends Facade
{
    use \Condoedge\Utils\Facades\FacadeUtils;

    protected static function getFacadeAccessor()
    {
        return 'task-detail-model';
    }
}