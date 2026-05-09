<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\CampaignResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignResourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_sets_campaign_resource_count(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        CampaignResource::factory()->create([
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Lumber->value,
            'count' => 5,
        ]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/resources/lumber", ['count' => 10])
            ->assertRedirect();

        $this->assertDatabaseHas('campaign_resources', [
            'campaign_id' => $campaign->id,
            'resource_type' => 'lumber',
            'count' => 10,
        ]);
    }

    public function test_update_rejects_negative_count(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        CampaignResource::factory()->create([
            'campaign_id' => $campaign->id,
            'resource_type' => ResourceType::Metal->value,
            'count' => 5,
        ]);

        $this->actingAs($user)
            ->patch("/campaigns/{$campaign->id}/resources/metal", ['count' => -1])
            ->assertSessionHasErrors('count');
    }

    public function test_update_forbidden_for_non_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)
            ->patch("/campaigns/{$campaign->id}/resources/lumber", ['count' => 5])
            ->assertForbidden();
    }
}
