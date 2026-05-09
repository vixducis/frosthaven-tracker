<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\CharacterResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterResource extends Model
{
    /** @use HasFactory<CharacterResourceFactory> */
    use HasFactory;

    protected $fillable = ['character_id', 'resource_type', 'count'];

    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
