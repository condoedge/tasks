<?php

namespace Kompo\Tasks\Models\Enums;

enum TaskVisibilityEnum: int
{
    use \Kompo\Auth\Models\Traits\EnumKompo;

	case ALL = 1;
	case BOARD = 2;
    case MANAGERS = 3;

	public function label()
    {
        return match ($this)
        {
            static::ALL => __('translate.tasks.visibilities.all'),
            static::BOARD => __('translate.tasks.visibilities.board'),
            static::MANAGERS => __('translate.tasks.visibilities.managers'),
        };
    }
}
