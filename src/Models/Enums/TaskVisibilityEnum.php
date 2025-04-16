<?php

namespace Kompo\Tasks\Models\Enums;

enum TaskVisibilityEnum: int
{
    use \Condoedge\Utils\Models\Traits\EnumKompo;

	case ALL = 1;
	case BOARD = 2;
    case MANAGERS = 3;

	public function label()
    {
        return match ($this)
        {
            static::ALL => __('tasks.visibilities.all'),
            static::BOARD => __('tasks.visibilities.board'),
            static::MANAGERS => __('tasks.visibilities.managers'),
        };
    }
}
