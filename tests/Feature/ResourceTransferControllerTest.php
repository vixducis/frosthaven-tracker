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

class ResourceTransferControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_moves_resources_from_character_to_campaign_atomically(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 10,
        ]);
        CampaignResource::factory()->create([
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 2,
        ]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/transfers", [
                'transfers' => [
                    ['resource_type' => 'lumber', 'amount' => 4],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('character_resources', [
            'character_id' => $character->id,
            'resource_type' => 'lumber',
            'count' => 6,
        ]);
        $this->assertDatabaseHas('campaign_resources', [
            'campaign_id' => $campaign->id,
            'resource_type' => 'lumber',
            'count' => 6,
        ]);
    }

    public function test_transfer_fails_when_character_has_insufficient_resources(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        CharacterResource::factory()->create([
            'character_id' => $character->id,
            'resource_type' => ResourceType::Metal->value,
            'count' => 2,
        ]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/transfers", [
                'transfers' => [
                    ['resource_type' => 'metal', 'amount' => 5],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('character_resources', [
            'character_id' => $character->id,
            'resource_type' => 'metal',
            'count' => 2,
        ]);
    }

    public function test_transfer_validates_amount_must_be_at_least_one(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/transfers", [
                'transfers' => [
                    ['resource_type' => 'lumber', 'amount' => 0],
                ],
            ])
            ->assertSessionHasErrors('transfers.0.amount');
    }

    public function test_transfer_forbidden_for_non_owner_of_character(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);
        $other = User::factory()->create();
        $campaign->members()->attach($other->id);

        $this->actingAs($other)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/transfers", [
                'transfers' => [['resource_type' => 'lumber', 'amount' => 1]],
            ])
            ->assertForbidden();
    }

    public function test_transfer_forbidden_for_non_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $character = Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post("/campaigns/{$campaign->id}/characters/{$character->id}/transfers", [
                'transfers' => [['resource_type' => 'lumber', 'amount' => 1]],
            ])
            ->assertForbidden();
    }
}
