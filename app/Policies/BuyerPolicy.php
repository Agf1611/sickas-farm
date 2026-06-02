<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class BuyerPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'buyers';
    }
}
