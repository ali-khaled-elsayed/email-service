<?php

declare(strict_types=1);

namespace App\Console;

use App\Services\SuperAdminPermissionSync;
use Illuminate\Console\Command;

class SyncSuperAdminPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync-super-admin';

    protected $description = 'Grant all permissions to the super_admin role';

    public function handle(SuperAdminPermissionSync $sync): int
    {
        $sync->sync();

        $this->info('super_admin role now has all permissions.');

        return self::SUCCESS;
    }
}
