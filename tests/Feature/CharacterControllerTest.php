<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\CampaignResource;
use App\Models\Character;
use App\Models\CharacterResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_character_with_all_resource_types(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters", ['name' => 'Quatryl Tinkerer'])
            ->assertRedirect();

        $character = Character::first();
        $this->assertNotNull($character);
        $this->assertSame('Quatryl Tinkerer', $character->name);
        $this->assertSame($campaign->id, $character->campaign_id);
        $this->assertSame($user->id, $character->user_id);

        $this->assertCount(count(ResourceType::cases()), $character->resources);
    }

    public function test_store_prevents_duplicate_character_in_same_campaign(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters", ['name' => 'Second Character'])
            ->assertSessionHasErrors('name');

        $this->assertCount(1, $campaign->characters);
    }

    public function test_store_forbidden_for_non_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post("/campaigns/{$campaign->id}/characters", ['name' => 'Test'])
            ->assertForbidden();
    }

    public function test_show_renders_character_page(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get("/campaigns/{$campaign->id}/characters/{$character->id}")
            ->assertInertia(fn ($page) => $page->component('campaigns/characters/show')
                ->has('campaign')
                ->has('character')
                ->has('resourceTypes'));
    }

    public function test_update_saves_gold_and_experience(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'gold' => 10,
            'experience' => 50,
        ]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}", [
                'gold' => 25,
                'experience' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'gold' => 25,
            'experience' => 100,
        ]);
    }

    public function test_update_forbidden_for_non_owner_of_character(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);
        $other = User::factory()->create();
        $campaign->members()->attach($other->id);

        $this->actingAs($other)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}", [
                'gold' => 99,
                'experience' => 99,
            ])
            ->assertForbidden();
    }

    public function test_update_validates_non_negative_values(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/characters/{$character->id}", [
                'gold' => -5,
                'experience' => -10,
            ])
            ->assertSessionHasErrors(['gold', 'experience']);
    }

    public function test_retire_marks_character_as_retired_and_transfers_resources(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 5,
        ]);
        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Metal->value,
            'count' => 0,
        ]);
        CampaignResource::factory()->create([
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 2,
        ]);
        CampaignResource::factory()->create([
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Metal->value,
            'count' => 3,
        ]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/retire")
            ->assertRedirect("/campaigns/{$campaign->id}");

        $this->assertDatabaseHas('character_resources', [
            'character_id' => $character->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 0,
        ]);
        $this->assertDatabaseHas('campaign_resources', [
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 7,
        ]);
        // Zero-count resources are not transferred
        $this->assertDatabaseHas('campaign_resources', [
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Metal->value,
            'count' => 3,
        ]);
        $this->assertNotNull($character->fresh()->retired_at);
    }

    public function test_retire_forbidden_for_non_owner_of_character(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);
        $other = User::factory()->create();
        $campaign->members()->attach($other->id);

        $this->actingAs($other)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/retire")
            ->assertForbidden();
    }

    public function test_store_allows_new_character_after_retirement(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        Character::factory()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'retired_at' => now(),
        ]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters", ['name' => 'New Character'])
            ->assertRedirect();

        $this->assertSame(2, $campaign->characters()->count());
    }
}
