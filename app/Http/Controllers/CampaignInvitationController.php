<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CampaignInvitationController extends Controller
{
    /**
     * Generate or refresh the invite token for a campaign.
     */
    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('generateInvite', $campaign);

        $campaign->generateInviteToken();

        return back();
    }

    /**
     * Show the invitation accept page.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $campaign = Campaign::where('invite_token', $token)->firstOrFail();

        $user = request()->user();

        if ($campaign->user_id === $user->id || $campaign->members()->where('user_id', $user->id)->exists()) {
            return redirect()->route('campaigns.show', $campaign);
        }

        return Inertia::render('campaigns/invitation', [
            'campaign' => ['id' => $campaign->id, 'name' => $campaign->name],
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation and join the campaign.
     */
    public function update(Request $request, string $token): RedirectResponse
    {
        $campaign = Campaign::where('invite_token', $token)->firstOrFail();

        $user = $request->user();

        if ($campaign->user_id !== $user->id && ! $campaign->members()->where('user_id', $user->id)->exists()) {
            $campaign->members()->attach($user->id);
        }

        return redirect()->route('campaigns.show', $campaign);
    }
}
