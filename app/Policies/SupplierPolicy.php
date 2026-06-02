<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class SupplierPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'suppliers';
    }
}
