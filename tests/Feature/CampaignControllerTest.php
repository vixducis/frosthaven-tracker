<?php

namespace Tests\Feature;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_redirects_guests(): void
    {
        $this->get('/campaigns')->assertRedirect('/login');
    }

    public function test_index_shows_campaigns_list(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->count(2)->create(['user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get('/campaigns')
            ->assertInertia(fn ($page) => $page->component('campaigns/index'));
    }

    public function test_index_shows_campaigns_list_when_user_has_single_campaign(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->create(['user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get('/campaigns')
            ->assertInertia(fn ($page) => $page->component('campaigns/index'));
    }

    public function test_store_creates_campaign_with_all_resource_types(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/campaigns', ['name' => 'Frozen Crown'])
            ->assertRedirect();

        $campaign = Campaign::first();
        $this->assertNotNull($campaign);
        $this->assertSame('Frozen Crown', $campaign->name);
        $this->assertSame($user->id, $campaign->user_id);

        $this->assertCount(count(ResourceType::cases()), $campaign->resources);

        foreach (ResourceType::cases() as $type) {
            $this->assertDatabaseHas('campaign_resources', [
                'campaign_id' => $campaign->id,
                'resource_type' => $type->value,
                'count' => 0,
            ]);
        }
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/campaigns', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_show_renders_campaign_page(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get("/campaigns/{$campaign->id}")
            ->assertInertia(fn ($page) => $page->component('campaigns/show')
                ->has('campaign')
                ->has('resourceTypes'));
    }

    public function test_show_passes_user_has_character_false_when_no_character(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get("/campaigns/{$campaign->id}")
            ->assertInertia(fn ($page) => $page->where('userHasCharacter', false));
    }

    public function test_show_passes_user_has_character_true_when_character_exists(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        Character::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)
            ->get("/campaigns/{$campaign->id}")
            ->assertInertia(fn ($page) => $page->where('userHasCharacter', true));
    }

    public function test_show_forbidden_for_non_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get("/campaigns/{$campaign->id}")
            ->assertForbidden();
    }
}
