<?php

namespace App\Policies;

use App\Models\CreditApplication;
use App\Models\User;

class CreditApplicationPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('marketing') && $user->branch_id !== null;
    }

    public function view(User $user, CreditApplication $application): bool
    {
        return $user->hasRole('approver', 'backoffice') ||
            ($user->hasRole('marketing') && $application->marketing_id === $user->id);
    }

    public function decide(User $user): bool
    {
        return $user->hasRole('approver');
    }

    public function issue(User $user): bool
    {
        return $user->hasRole('backoffice');
    }

    public function uploadSigned(User $user, CreditApplication $application): bool
    {
        return $user->hasRole('marketing') && $application->marketing_id === $user->id;
    }

    public function disburse(User $user): bool
    {
        return $user->hasRole('backoffice');
    }
}
