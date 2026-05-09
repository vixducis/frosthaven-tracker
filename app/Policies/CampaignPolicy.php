<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Any authenticated user who is a member of the campaign can view it.
     * Membership means: owning it, being an invited member, or having a character in it.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        if ($campaign->user_id === $user->id) {
            return true;
        }

        if ($campaign->members()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $campaign->characters()->where('user_id', $user->id)->exists();
    }

    /**
     * Only the campaign owner can generate an invite link.
     */
    public function generateInvite(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id;
    }
}
