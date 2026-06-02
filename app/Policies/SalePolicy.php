<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class SalePolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'sales';
    }
}
