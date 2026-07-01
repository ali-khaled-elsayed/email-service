<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthLogEvent: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Failed = 'failed';
    case Lockout = 'lockout';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::Failed => 'Failed login',
            self::Lockout => 'Lockout',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Login => 'success',
            self::Logout => 'gray',
            self::Failed, self::Lockout => 'danger',
        };
    }
}
