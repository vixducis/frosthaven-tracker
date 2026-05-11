<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $campaigns = $request->user()->campaigns()->latest()->get();

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $campaign = $request->user()->campaigns()->create($validated);

        foreach (ResourceType::cases() as $type) {
            $campaign->resources()->create(['resource_type' => $type->value, 'count' => 0]);
        }

        return redirect()->route('campaigns.show', $campaign);
    }

    public function show(Request $request, Campaign $campaign): Response
    {
        Gate::authorize('view', $campaign);

        $campaign->load(['resources', 'characters.resources', 'characters.user']);

        $resourceTypes = array_map(
            fn (ResourceType $type) => ['value' => $type->value, 'label' => $type->label()],
            ResourceType::cases(),
        );

        $isOwner = $campaign->user_id === $request->user()->id;

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
            'resourceTypes' => $resourceTypes,
            'isOwner' => $isOwner,
            'inviteLink' => $isOwner && $campaign->invite_token
                ? route('campaigns.invitations.show', $campaign->invite_token)
                : null,
            'userHasCharacter' => $campaign->characters()->where('user_id', $request->user()->id)->whereNull('retired_at')->exists(),
            'currentUserId' => $request->user()->id,
            'monsterLevel' => $campaign->monsterLevel(),
        ]);
    }
}
