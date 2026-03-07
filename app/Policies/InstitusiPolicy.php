<?php

namespace App\Policies;

use App\Models\Institusi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InstitusiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasPermissionTo('read institution')) {
            return Response::allow();
        }
        return Response::denyAsNotFound(__('Not Found'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Institusi $institusi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        if ($user->hasPermissionTo('create institution')) {
            return Response::allow();
        }
        return Response::denyAsNotFound(__('Not Found'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Institusi $institusi): Response
    {
        if ($user->hasPermissionTo('update institution')) {
            return Response::allow();
        }
        return Response::denyAsNotFound(__('Not Found'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Institusi $institusi): Response
    {
        if ($user->hasPermissionTo('delete institution')) {
            return Response::allow();
        }
        return Response::denyAsNotFound(__('Not Found'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Institusi $institusi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Institusi $institusi): bool
    {
        return false;
    }
}
