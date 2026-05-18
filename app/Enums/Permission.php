<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case ViewDashboard = 'view_dashboard';
    case ManageApplications = 'manage_applications';
    case ManageProviders = 'manage_providers';
    case ViewEmailLogs = 'view_email_logs';
    case ManageEmailLogs = 'manage_email_logs';
    case ManageEmailTemplates = 'manage_email_templates';
    case ViewFailedAttempts = 'view_failed_attempts';
    case ManageSettings = 'manage_settings';
    case ManageUsers = 'manage_users';
    case ManageRoles = 'manage_roles';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
