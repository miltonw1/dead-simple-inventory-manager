<?php

namespace App\Traits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasUserScope
{
    /**
     * Scope a query to get records based on user role.
     * If user is admin, return all records.
     * Otherwise, return only user's records.
     */
    public function scopeForUser(Builder $query, User $user, bool $withTrashed = false): Builder
    {
        if ($user->is_admin) {
            if ($withTrashed && method_exists($query, 'withTrashed')) {
                return $query->withTrashed();
            }

            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
