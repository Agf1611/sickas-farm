<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class ExpensePolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'expenses';
    }
}
