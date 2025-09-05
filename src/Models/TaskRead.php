<?php

namespace Kompo\Tasks\Models;

use Condoedge\Utils\Models\ModelBase;
use Kompo\Auth\Models\Traits\BelongsToUserTrait;
use Kompo\Tasks\Facades\TaskDetailModel;

class TaskRead extends ModelBase
{
	use BelongsToUserTrait;

    public function taskDetail()
    {
        return $this->belongsTo(TaskDetailModel::getClass());
    }
}
