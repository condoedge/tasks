<?php

use Kompo\Auth\Facades\UserModel;
use Kompo\Auth\Facades\RoleModel;

return [
    'task-model-namespace' => Kompo\Tasks\Models\Task::class,
    'task-detail-model-namespace' => Kompo\Tasks\Models\TaskDetail::class,

    // 'assignables' => [
    //     UserModel::getClass(),
    //     RoleModel::getClass(),
    // ]
];