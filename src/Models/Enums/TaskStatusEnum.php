<?php

namespace Kompo\Tasks\Models\Enums;

enum TaskStatusEnum: int
{
    use \Kompo\Auth\Models\Traits\EnumKompo;

	case OPEN = 0;
	case PENDING = 1;
    case PROCESSING = 2;
    case CLOSED = 3;

	public function label()
    {
        return match ($this)
        {
            static::OPEN => __('tasks.statuses.open'),
            static::PENDING => __('tasks.statuses.pending'),
            static::PROCESSING => __('tasks.statuses.processing'),
            static::CLOSED => __('tasks.statuses.done')
        };
    }
}
