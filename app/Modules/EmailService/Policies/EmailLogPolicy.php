<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Policies;

use App\Models\User;
use App\Modules\EmailService\Models\EmailLog;

class EmailLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailLog $emailLog): bool
    {
        return true;
    }

    public function update(User $user, EmailLog $emailLog): bool
    {
        return true;
    }
}
