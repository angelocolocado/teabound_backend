<?php

namespace App\Policies;

use App\Enum\UserRoleEnum;
use App\Models\MenuContent;
use App\Models\User;

class MenuContentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRoleEnum::admin,
            UserRoleEnum::cashier,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MenuContent $menuContent): bool
    {
        return in_array($user->role, [
            UserRoleEnum::admin,
            UserRoleEnum::cashier,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRoleEnum::admin,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MenuContent $menuContent): bool
    {
        return in_array($user->role, [
            UserRoleEnum::admin,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MenuContent $menuContent): bool
    {
        return in_array($user->role, [
            UserRoleEnum::admin,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MenuContent $menuContent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MenuContent $menuContent): bool
    {
        return false;
    }
}
