<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CharacterResourceController extends Controller
{
    public function update(Request $request, Campaign $campaign, Character $character, string $resourceType): RedirectResponse
    {
        Gate::authorize('update', $character);

        $type = ResourceType::from($resourceType);

        $validated = $request->validate([
            'count' => 'required|integer|min:0',
        ]);

        $character->resources()
            ->where('resource_type', $type->value)
            ->update(['count' => $validated['count']]);

        return back();
    }
}
