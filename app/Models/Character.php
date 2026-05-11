<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    protected $fillable = ['campaign_id', 'user_id', 'name', 'gold', 'experience', 'retired_at'];

    protected $appends = ['level'];

    protected function casts(): array
    {
        return [
            'retired_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CharacterResource::class);
    }

    /**
     * Get the resource count for a given type, defaulting to 0.
     */
    public function resourceCount(ResourceType $type): int
    {
        return $this->resources->firstWhere('resource_type', $type->value)?->count ?? 0;
    }

    /**
     * XP thresholds for each level (index = level - 1).
     *
     * @var array<int, int>
     */
    public const XP_THRESHOLDS = [0, 45, 95, 150, 210, 275, 345, 420, 500];

    /**
     * Get the character's level based on their current experience.
     */
    public function getLevelAttribute(): int
    {
        $level = 1;
        foreach (self::XP_THRESHOLDS as $index => $threshold) {
            if ($this->experience >= $threshold) {
                $level = $index + 1;
            }
        }

        return $level;
    }
}
