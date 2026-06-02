<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class ExpenseCategoryPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'expense-categories';
    }
}
