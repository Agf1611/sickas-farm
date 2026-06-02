<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class PenPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'pens';
    }
}
