<?php

namespace App\Policies;

use App\Models\Uploads;
use App\Models\User;

class UploadsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Uploads $uploads): bool {
        return $user->id == $uploads->users_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Uploads $uploads): bool {
        return $user->id == $uploads->users_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Uploads $uploads): bool {
        return $user->id == $uploads->users_id;
    }
}
