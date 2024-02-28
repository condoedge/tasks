<?php

namespace Kompo\TasksSeeders;

use Illuminate\Database\Seeder;
use Kompo\TasksSeeders\Traits\PermissionTrait;

class TasksPermissionsTableSeeder extends Seeder
{
    use PermissionTrait;

    public function run()
    {
        $this->createPermission(
            'tasks:create',
            'Create tasks',
            'Create tasks',
        );

        $this->createPermission(
            'tasks:updateOfTeam',
            'Update only tasks of your team',
            'Update tasks of your team',
        );

        $this->createPermission(
            'tasks:deleteOfTeam',
            'Delete only tasks of your team',
            'Delete tasks of your team',
        );

        $this->createPermission(
            'tasks:delete',
            'Delete tasks',
            'Delete tasks all tasks',
        );

        $this->createPermission(
            'tasks:update',
            'Update tasks',
            'Update tasks all tasks',
        );

        $this->createPermission(
            'tasks:close',
            'Close tasks',
            'Close tasks all tasks',
        );

        $this->createPermission(
            'tasks:closeAssigned',
            'Close your own tasks',
            'Close your own tasks',
        );

        $this->createPermission(
            'tasks:closeOfTeam',
            'Close only tasks of your team',
            'Close tasks of your team',
        );
    }
}
