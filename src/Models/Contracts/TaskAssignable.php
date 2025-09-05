<?php

namespace Kompo\Tasks\Models\Contracts;

interface TaskAssignable
{
    public function getAllRelatedTaskUserAssignables($taskId);
    public static function getAllTaskRelatedToUserQuery($query, $userId);

    public function getDisplayAttribute();

    public function getMorphClass();
    public function getIdForTask();
}