<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SickasFarmPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SickasFarmRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (SickasFarmPermissions::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (SickasFarmPermissions::rolePermissions() as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }

        $this->assignInitialAdminRole();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function assignInitialAdminRole(): void
    {
        $adminUsers = User::query()
            ->whereIn('email', ['admin@sickas.local'])
            ->get();

        if ($adminUsers->isEmpty() && User::query()->count() === 1) {
            $adminUsers = User::query()->limit(1)->get();
        }

        $adminUsers->each(function (User $user): void {
            if (! $user->hasAnyRole(array_keys(SickasFarmPermissions::rolePermissions()))) {
                $user->assignRole(SickasFarmPermissions::SUPER_ADMIN);
            }
        });
    }
}
