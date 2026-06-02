<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class BusinessProfilePolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'business-profiles';
    }
}
