<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class StockMovementPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'stock-movements';
    }
}
