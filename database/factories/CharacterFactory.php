<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
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
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'gold' => fake()->numberBetween(0, 100),
            'experience' => fake()->numberBetween(0, 500),
        ];
    }
}
