<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminPermissionSync
{
    public function sync(): void
    {
        if (! $this->tablesExist()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        $superAdmin = Role::findOrCreate(RoleEnum::SuperAdmin->value, 'web');
        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $superAdmin->syncPermissions($allPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncIfOutdated(): void
    {
        if (! $this->tablesExist()) {
            return;
        }

        $superAdmin = Role::query()
            ->where('name', RoleEnum::SuperAdmin->value)
            ->where('guard_name', 'web')
            ->first();

        if (! $superAdmin) {
            $this->sync();

            return;
        }

        $expected = count(PermissionEnum::cases());
        $assigned = $superAdmin->permissions()->count();

        if ($assigned < $expected) {
            $this->sync();
        }
    }

    private function tablesExist(): bool
    {
        try {
            return Schema::hasTable('roles')
                && Schema::hasTable('permissions')
                && Schema::hasTable('role_has_permissions');
        } catch (\Throwable) {
            return false;
        }
    }
}
