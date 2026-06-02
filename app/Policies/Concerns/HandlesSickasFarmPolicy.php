<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\SickasFarmPermissions;

trait HandlesSickasFarmPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(SickasFarmPermissions::SUPER_ADMIN) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.view');
    }

    public function view(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.manage');
    }

    public function update(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.delete');
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }

    abstract protected function permissionPrefix(): string;
}
