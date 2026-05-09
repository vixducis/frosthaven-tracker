<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterResourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_sets_character_resource_count(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);
        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Hide->value,
            'count' => 3,
        ]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}/resources/hide", ['count' => 7])
            ->assertRedirect();

        $this->assertDatabaseHas('character_resources', [
            'character_id' => $character->id,
            'resource_type' => 'hide',
            'count' => 7,
        ]);
    }

    public function test_update_forbidden_for_non_owner_of_character(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);
        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Hide->value,
            'count' => 3,
        ]);
        $other = User::factory()->create();
        $campaign->members()->attach($other->id);

        $this->actingAs($other)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}/resources/hide", ['count' => 99])
            ->assertForbidden();
    }

    public function test_update_rejects_negative_count(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);
        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Arrowvine->value,
            'count' => 3,
        ]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}/resources/arrowvine", ['count' => -1])
            ->assertSessionHasErrors('count');
    }
}
