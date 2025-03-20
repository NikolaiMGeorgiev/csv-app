<?php

namespace App\Policies;

use App\Models\Products;
use App\Models\User;

class ProductsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Products $products) {
        return $user->id == $products->users_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Products $products): bool {
        return $user->id == $products->users_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Products $products): bool {
        return $user->id == $products->users_id;
    }
}
