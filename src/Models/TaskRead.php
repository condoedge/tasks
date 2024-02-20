<?php

namespace Kompo\Tasks\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kompo\Auth\Models\ModelBase;
use Kompo\Auth\Models\Traits\BelongsToUserTrait;

class TaskRead extends ModelBase
{
	use BelongsToUserTrait;

    public function taskDetail(): BelongsTo
    {
        return $this->belongsTo(TaskDetail::class);
    }
}
