<?php

namespace Kompo\Tasks\Models\Contracts;

interface Taskable
{
    public function getSubmenu();
    public function getName();
}