<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class SheepIncidentRecordPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'incidents';
    }
}
