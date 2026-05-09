<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_invite_link(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post("/campaigns/{$campaign->id}/invitations")
            ->assertRedirect();

        $this->assertNotNull($campaign->fresh()->invite_token);
    }

    public function test_non_owner_cannot_generate_invite_link(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post("/campaigns/{$campaign->id}/invitations")
            ->assertForbidden();
    }

    public function test_show_invitation_page_for_new_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);
        $invitee = User::factory()->create();

        $this->withoutVite()->actingAs($invitee)
            ->get('/invitations/test-token-123')
            ->assertInertia(fn ($page) => $page->component('campaigns/invitation')
                ->where('campaign.name', $campaign->name)
                ->where('token', 'test-token-123'));
    }

    public function test_show_invitation_redirects_owner_to_campaign(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);

        $this->actingAs($owner)
            ->get('/invitations/test-token-123')
            ->assertRedirect("/campaigns/{$campaign->id}");
    }

    public function test_show_invitation_redirects_existing_member_to_campaign(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);
        $member = User::factory()->create();
        $campaign->members()->attach($member->id);

        $this->actingAs($member)
            ->get('/invitations/test-token-123')
            ->assertRedirect("/campaigns/{$campaign->id}");
    }

    public function test_show_invitation_returns_404_for_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/invitations/invalid-token')
            ->assertNotFound();
    }

    public function test_accepting_invitation_adds_user_as_member(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);
        $invitee = User::factory()->create();

        $this->actingAs($invitee)
            ->post('/invitations/test-token-123')
            ->assertRedirect("/campaigns/{$campaign->id}");

        $this->assertTrue($campaign->members()->where('user_id', $invitee->id)->exists());
    }

    public function test_accepting_invitation_as_existing_member_does_not_duplicate(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);
        $member = User::factory()->create();
        $campaign->members()->attach($member->id);

        $this->actingAs($member)
            ->post('/invitations/test-token-123')
            ->assertRedirect("/campaigns/{$campaign->id}");

        $this->assertSame(1, $campaign->members()->where('user_id', $member->id)->count());
    }

    public function test_accepting_invitation_as_owner_does_not_add_to_members(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);

        $this->actingAs($owner)
            ->post('/invitations/test-token-123')
            ->assertRedirect("/campaigns/{$campaign->id}");

        $this->assertFalse($campaign->members()->where('user_id', $owner->id)->exists());
    }

    public function test_member_can_view_campaign_after_accepting_invite(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $campaign->members()->attach($member->id);

        $this->withoutVite()->actingAs($member)
            ->get("/campaigns/{$campaign->id}")
            ->assertOk();
    }

    public function test_invite_link_shown_in_campaign_show_for_owner(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);

        $this->withoutVite()->actingAs($owner)
            ->get("/campaigns/{$campaign->id}")
            ->assertInertia(fn ($page) => $page
                ->where('isOwner', true)
                ->whereNot('inviteLink', null));
    }

    public function test_invite_link_not_shown_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $owner->id, 'invite_token' => 'test-token-123']);
        $member = User::factory()->create();
        $campaign->members()->attach($member->id);

        $this->withoutVite()->actingAs($member)
            ->get("/campaigns/{$campaign->id}")
            ->assertInertia(fn ($page) => $page
                ->where('isOwner', false)
                ->where('inviteLink', null));
    }
}
