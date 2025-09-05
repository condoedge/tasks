<?php

namespace Kompo\Tasks\Facades;

use Kompo\Komponents\Form\KompoModelFacade;

/**
 * @mixin \Kompo\Tasks\Models\TaskDetail
 */
class TaskDetailModel extends KompoModelFacade
{
    protected static function getModelBindKey()
    {
        return 'task-detail-model';
    }
}
