<?php

namespace Kompo\Tasks\Services;

use Illuminate\Support\Facades\Route;
use Kompo\Tasks\Components\Tasks\TaskForm;
use Kompo\Tasks\Components\Tasks\TasksKanban;
use Kompo\Tasks\Components\Tasks\TasksManager;

class TaskService 
{
    public static function setRoutes()
    {
        Route::get('tasks-manager', TasksManager::class)->name('tasks.manager');
        Route::get('tasks-kanban/{mine_urgent?}', TasksKanban::class)->name('tasks.kanban');

        Route::get('/task/{id?}', TaskForm::class)->name('task.form');
    }
}