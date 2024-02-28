<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TasksPermissionsTableSeeder extends Seeder
{
    use PermissionTrait;

    public function run()
    {
        $this->createPermission(
            'tasks:create',
            'Create tasks',
        );

        $this->createPermission(
            'tasks:updateOfTeam',
            'Update only tasks of your team',
        );

        $this->createPermission(
            'tasks:deleteOfTeam',
            'Delete only tasks of your team',
        );

        $this->createPermission(
            'tasks:delete',
            'Delete tasks',
        );

        $this->createPermission(
            'tasks:update',
            'Update tasks',
        );

        $this->createPermission(
            'tasks:close',
            'Close tasks',
        );

        $this->createPermission(
            'tasks:closeAssigned',
            'Close your own tasks',
        );

        $this->createPermission(
            'tasks:closeOfTeam',
            'Close only tasks of your team',
        );
    }
}
