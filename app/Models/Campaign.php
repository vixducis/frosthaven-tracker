<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'invite_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CampaignResource::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function generateInviteToken(): string
    {
        $token = Str::random(32);
        $this->update(['invite_token' => $token]);

        return $token;
    }

    /**
     * Get the resource count for a given type, defaulting to 0.
     */
    public function resourceCount(ResourceType $type): int
    {
        return $this->resources->firstWhere('resource_type', $type->value)?->count ?? 0;
    }

    /**
     * Calculate the recommended monster level based on the average active character level.
     *
     * Monster level = ceil(average character level / 2), or 0 when no active characters.
     * Requires the characters relationship to be loaded.
     */
    public function monsterLevel(): int
    {
        $active = $this->characters->whereNull('retired_at');

        if ($active->isEmpty()) {
            return 0;
        }

        return (int) ceil($active->avg(fn (Character $c) => $c->level) / 2);
    }
}
