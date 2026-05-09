<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\Character;
use App\Models\CharacterResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterResource>
 */
class CharacterResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'resource_type' => fake()->randomElement(ResourceType::cases())->value,
            'count' => fake()->numberBetween(0, 20),
        ];
    }
}
