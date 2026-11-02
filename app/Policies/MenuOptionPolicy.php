<?php

namespace App\Policies;

use App\Enum\UserRoleEnum;
use App\Models\MenuOption;
use App\Models\User;

class MenuOptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRoleEnum::admin, UserRoleEnum::cashier], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MenuOption $menuOption): bool
    {
        return in_array($user->role, [UserRoleEnum::admin, UserRoleEnum::cashier], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRoleEnum::admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MenuOption $menuOption): bool
    {
        return $user->role === UserRoleEnum::admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MenuOption $menuOption): bool
    {
        return $user->role === UserRoleEnum::admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MenuOption $menuOption): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MenuOption $menuOption): bool
    {
        return false;
    }
}
