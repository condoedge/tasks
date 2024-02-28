<?php

namespace Kompo\TasksSeeders;

use Illuminate\Database\Seeder;

class TaskDetailPermissionsTableSeeder extends Seeder
{
    use PermissionTrait;

    public function run()
    {
        $this->createPermission(
            'taskDetails:create',
            'Create task details',
        );

        $this->createPermission(
            'taskDetails:updateOfTeam',
            'Update only task details of your team',
        );

        $this->createPermission(
            'taskDetails:deleteOfTeam',
            'Delete only task details of your team',
        );

        $this->createPermission(
            'taskDetails:delete',
            'Delete task details',
        );

        $this->createPermission(
            'taskDetails:update',
            'Update task details',
        );
    }
}
