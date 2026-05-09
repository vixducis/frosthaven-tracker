<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\CampaignResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignResource extends Model
{
    /** @use HasFactory<CampaignResourceFactory> */
    use HasFactory;

    protected $fillable = ['campaign_id', 'resource_type', 'count'];

    protected function casts(): array
    {
        return [
            'resource_type' => ResourceType::class,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
