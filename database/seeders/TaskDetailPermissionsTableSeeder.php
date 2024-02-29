<?php

namespace Kompo\TasksSeeders;

use Illuminate\Database\Seeder;
use Kompo\TasksSeeders\Traits\PermissionTrait;

class TaskDetailPermissionsTableSeeder extends Seeder
{
    use PermissionTrait;

    public function run()
    {
        $this->createPermission(
            'taskDetails:create',
            'Create task details',
            'Create task details',
        );

        $this->createPermission(
            'taskDetails:updateOfTeam',
            'Update only task details of your team',
            'Update task details of your team',
        );

        $this->createPermission(
            'taskDetails:deleteOfTeam',
            'Delete only task details of your team',
            'Delete task details of your team',
        );

        $this->createPermission(
            'taskDetails:delete',
            'Delete task details',
            'Delete task details all task details',
        );

        $this->createPermission(
            'taskDetails:update',
            'Update task details',
            'Update task details all task details',
        );
    }
}
