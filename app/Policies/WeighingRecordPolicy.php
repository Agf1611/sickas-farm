<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesSickasFarmPolicy;

class WeighingRecordPolicy
{
    use HandlesSickasFarmPolicy;

    protected function permissionPrefix(): string
    {
        return 'weighing';
    }
}
