<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    /**
     * Only the character's owner can update it.
     */
    public function update(User $user, Character $character): bool
    {
        return $character->user_id === $user->id;
    }
}
