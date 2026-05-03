<?php

namespace App\Policies;

use App\Models\Processo;
use App\Models\User;

class ProcessoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }

    public function delete(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }

    public function uploadDocument(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }

    public function manageSignatarios(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }

    public function sendConvites(User $user, Processo $processo): bool
    {
        return (int) $user->id === (int) $processo->responsible_user_id;
    }
}
