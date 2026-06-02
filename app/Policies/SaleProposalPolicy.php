<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class SaleProposalPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'sale-proposals';
    }
}
