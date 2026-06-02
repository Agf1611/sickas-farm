<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class UserPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'users';
    }
}
