<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        Gate::authorize('view', $campaign);

        $validated = $request->validate(['name' => 'required|string|max:255']);

        if ($campaign->characters()->where('user_id', $request->user()->id)->whereNull('retired_at')->exists()) {
            return back()->withErrors(['name' => 'You already have a character in this campaign.']);
        }

        $character = $campaign->characters()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
        ]);

        foreach (ResourceType::cases() as $type) {
            $character->resources()->create(['resource_type' => $type->value, 'count' => 0]);
        }

        return redirect()->route('campaigns.characters.show', [$campaign, $character]);
    }

    public function show(Campaign $campaign, Character $character): Response
    {
        Gate::authorize('view', $campaign);

        $character->load('resources');

        $resourceTypes = array_map(
            fn (ResourceType $type) => ['value' => $type->value, 'label' => $type->label()],
            ResourceType::cases(),
        );

        return Inertia::render('campaigns/characters/show', [
            'campaign' => $campaign,
            'character' => $character,
            'resourceTypes' => $resourceTypes,
            'isOwner' => auth()->id() === $character->user_id,
        ]);
    }

    public function retire(Campaign $campaign, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        DB::transaction(function () use ($campaign, $character) {
            $character->resources()->each(function ($characterResource) use ($campaign) {
                if ($characterResource->count > 0) {
                    $campaign->resources()
                        ->where('resource_type', $characterResource->resource_type->value)
                        ->increment('count', $characterResource->count);

                    $characterResource->update(['count' => 0]);
                }
            });

            $character->update(['retired_at' => now()]);
        });

        return redirect()->route('campaigns.show', $campaign);
    }

    public function update(Request $request, Campaign $campaign, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $request->validate([
            'gold' => 'required|integer|min:0',
            'experience' => 'required|integer|min:0',
        ]);

        $character->update($validated);

        return back();
    }
}
