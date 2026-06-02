<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class FatteningBatchPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'batches';
    }
}
