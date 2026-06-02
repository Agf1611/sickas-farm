<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class LivestockTypePolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'livestock-types';
    }
}
