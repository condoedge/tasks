<?php

use Kompo\Auth\Facades\UserModel;
use Kompo\Auth\Facades\RoleModel;

return [
    'task-model-namespace' => Kompo\Tasks\Models\Task::class,
    'task-detail-model-namespace' => Kompo\Tasks\Models\TaskDetail::class,

    // 'assignables' => [
    //     'person' => [
    //         'model' => UserModel::getClass(),
    //         'label' => 'tasks.person',
    //         'placeholder' => 'tasks.pick-a-person',
    //         'multiple' => true,
    //         'icon' => 'profile',
    //     ],
    //     'position' => [
    //         'model' => RoleModel::getClass(),
    //         'label' => 'tasks.position',
    //         'placeholder' => 'tasks.pick-a-position',
    //         'multiple' => false,
    //         'icon' => 'briefcase',
    //     ],
    // ]
];
