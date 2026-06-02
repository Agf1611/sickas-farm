<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class SheepPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'sheep';
    }
}
