<?php

namespace App\Policies;

use App\Models\User;

class TenantPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && (bool) $user->restaurant?->isAvailable();
    }

    public function view(User $user, object $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, object $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, object $model): bool
    {
        return $this->owns($user, $model);
    }

    private function owns(User $user, object $model): bool
    {
        return $user->is_active && $user->restaurant?->isAvailable() && (int) $user->restaurant_id === (int) $model->restaurant_id;
    }
}
