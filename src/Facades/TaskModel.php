<?php

namespace Kompo\Tasks\Facades;

use Kompo\Komponents\Form\KompoModelFacade;

/**
 * @mixin \Kompo\Tasks\Models\Task
 */
class TaskModel extends KompoModelFacade
{
    protected static function getModelBindKey()
    {
        return 'task-model';
    }
}