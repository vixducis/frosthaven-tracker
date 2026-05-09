<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\CampaignResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignResource>
 */
class CampaignResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'resource_type' => fake()->randomElement(ResourceType::cases())->value,
            'count' => fake()->numberBetween(0, 20),
        ];
    }
}
