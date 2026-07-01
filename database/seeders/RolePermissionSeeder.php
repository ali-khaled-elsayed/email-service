<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Services\SuperAdminPermissionSync;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(SuperAdminPermissionSync::class)->sync();

        $rolePermissions = [
            RoleEnum::Admin->value => [
                PermissionEnum::ViewDashboard->value,
                PermissionEnum::ManageApplications->value,
                PermissionEnum::ManageProviders->value,
                PermissionEnum::ViewEmailLogs->value,
                PermissionEnum::ManageEmailLogs->value,
                PermissionEnum::ManageEmailTemplates->value,
                PermissionEnum::ViewFailedAttempts->value,
                PermissionEnum::ManageSettings->value,
                PermissionEnum::ViewAuthLogs->value,
                PermissionEnum::ViewSystemLogs->value,
            ],
            RoleEnum::Operator->value => [
                PermissionEnum::ViewDashboard->value,
                PermissionEnum::ViewEmailLogs->value,
                PermissionEnum::ManageEmailLogs->value,
                PermissionEnum::ViewFailedAttempts->value,
            ],
            RoleEnum::Viewer->value => [
                PermissionEnum::ViewDashboard->value,
                PermissionEnum::ViewEmailLogs->value,
                PermissionEnum::ViewFailedAttempts->value,
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
