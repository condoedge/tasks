<?php

namespace Kompo\Tasks\Models;

use Condoedge\Utils\Models\ModelBase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kompo\Auth\Models\Traits\BelongsToUserTrait;

class TaskRead extends ModelBase
{
	use BelongsToUserTrait;

    public function taskDetail(): BelongsTo
    {
        return $this->belongsTo(TaskDetail::class);
    }
}
