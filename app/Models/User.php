<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->can(Permission::ViewDashboard->value);
    }

    /**
     * Super admin bypasses all permission checks.
     */
    public function can($ability, $arguments = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return parent::can($ability, $arguments);
    }
}
