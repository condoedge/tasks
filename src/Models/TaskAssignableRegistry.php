<?php

namespace Kompo\Tasks\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Kompo\Auth\Facades\UserModel;
use Kompo\Tasks\Models\Contracts\TaskAssignable;

class TaskAssignableRegistry
{
    protected static ?Collection $cachedConfigs = null;

    public static function configs(): Collection
    {
        if (static::$cachedConfigs !== null) {
            return static::$cachedConfigs;
        }

        $configs = config('kompo-tasks.assignables') ?: [
            'person' => [
                'model' => UserModel::getClass(),
                'label' => 'tasks.person',
                'multiple' => true,
                'icon' => 'profile',
            ],
        ];

        return static::$cachedConfigs = collect($configs)
            ->mapWithKeys(function ($config, $key) {
                $normalized = static::normalize($config, $key);

                return $normalized ? [$normalized['key'] => $normalized] : [];
            });
    }

    public static function config(string $type): array
    {
        return static::configs()->get($type) ?: static::configs()->first();
    }

    public static function classes(): Collection
    {
        return static::configs()
            ->pluck('model')
            ->filter(fn ($class) => $class && class_exists($class) && is_subclass_of($class, TaskAssignable::class))
            ->unique()
            ->values();
    }

    public static function typeForClass(?string $class): ?string
    {
        if (!$class) {
            return null;
        }

        return static::configs()
            ->filter(fn ($config) => static::classesMatch($config['model'], $class))
            ->keys()
            ->first();
    }

    public static function classFromAssignation($assignation): ?string
    {
        return Relation::getMorphedModel($assignation->assignable_type) ?: $assignation->assignable_type;
    }

    public static function assignationMatchesClass($assignation, string $class): bool
    {
        $assignationClass = static::classFromAssignation($assignation);

        return $assignationClass !== null && static::classesMatch($assignationClass, $class);
    }

    public static function classesMatch(string $a, string $b): bool
    {
        return $a === $b || is_a($a, $b, true) || is_a($b, $a, true);
    }

    public static function isUserClass(?string $class): bool
    {
        return $class !== null && static::classesMatch($class, UserModel::getClass());
    }

    public static function keyNameFor(string $class): string
    {
        return TaskAssignation::taskAssignableKeyName(new $class());
    }

    protected static function normalize($config, $key): ?array
    {
        $class = is_string($config) ? $config : ($config['model'] ?? $config['class'] ?? null);

        if (!$class || !class_exists($class)) {
            return null;
        }

        $key = is_string($key) ? $key : static::keyFromClass($class);

        return [
            'key' => $key,
            'model' => $class,
            'label' => is_array($config) ? ($config['label'] ?? 'tasks.'.$key) : 'tasks.'.$key,
            'placeholder' => is_array($config) ? ($config['placeholder'] ?? null) : null,
            'multiple' => is_array($config) ? ($config['multiple'] ?? static::isUserClass($class)) : static::isUserClass($class),
            'icon' => is_array($config) ? ($config['icon'] ?? null) : null,
        ];
    }

    protected static function keyFromClass(string $class): string
    {
        return static::isUserClass($class) ? 'person' : strtolower(class_basename($class));
    }
}
