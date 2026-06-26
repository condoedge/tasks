<?php

namespace Kompo\Tasks\Components\Tasks\Concerns;

/**
 * Renders a consistent "read-only / no permission" notice so users understand
 * why a task or task detail can't be edited, instead of silently missing controls.
 */
trait ShowsPermissionNotice
{
    protected function readOnlyPermissionNotice($messageKey = 'tasks.read-only-no-permission')
    {
        return _Flex(
            _Sax('info-circle', 20)->class('text-warning shrink-0 mt-0.5'),
            _Html($messageKey)->class('text-sm'),
        )->class('gap-2 items-start p-3 rounded-lg bg-warning bg-opacity-10 border border-warning mb-2');
    }
}
