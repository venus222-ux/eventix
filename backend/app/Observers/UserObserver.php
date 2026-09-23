<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function creating(User $user)
    {
        if ($user->name) {
            $user->name = ucfirst(trim($user->name));
        }
    }

    public function saving(User $user)
    {
        if ($user->name) {
            $user->name = ucfirst(trim($user->name));
        }
    }
}
