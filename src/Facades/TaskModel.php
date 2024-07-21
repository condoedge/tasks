<?php

use Illuminate\Support\Facades\Facade;
use Kompo\Auth\Facades\FacadeUtils;

class TaskModel extends Facade
{
    use FacadeUtils;

    protected static function getFacadeAccessor()
    {
        return 'task-model';
    }
}