<?php

namespace Database\Seeders;

use Kompo\Auth\Models\Teams\Permission;

trait PermissionTrait 
{  
    protected function createPermission($key, $name, $description = null)
    {
        $p = new Permission();
        $p->permission_key = $key;
        $p->permission_name = $name;
        $p->permission_description = $description;
        $p->save();
    }
}