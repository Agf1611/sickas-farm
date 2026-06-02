<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class LivestockMarketPricePolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'market-prices';
    }
}
