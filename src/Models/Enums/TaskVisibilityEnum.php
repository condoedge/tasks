<?php

namespace Kompo\Tasks\Models\Enums;

use Kompo\Auth\Models\Teams\PermissionTypeEnum;

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

    /**
     * The permission a user must hold on the task's team to see (READ) or give (WRITE) this
     * level. ALL has none: it means everyone who can read the team's tasks.
     */
    public function permissionKey(): ?string
    {
        return match ($this)
        {
            static::ALL => null,
            static::BOARD => 'TaskVisibilityBoard',
            static::MANAGERS => 'TaskVisibilityManagers',
        };
    }

    /**
     * Levels $user may give a task on $teamId — ALL is always one of them.
     */
    public static function selectableBy($user, ?int $teamId): array
    {
        return collect(static::cases())->filter(
            fn (self $case) => !$case->permissionKey() || ($user && $user->hasPermission(
                $case->permissionKey(),
                PermissionTypeEnum::WRITE,
                teamIds: $teamId ? [$teamId] : null,
            ))
        )->values()->all();
    }
}
