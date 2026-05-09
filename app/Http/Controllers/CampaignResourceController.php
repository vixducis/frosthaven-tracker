<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CampaignResourceController extends Controller
{
    public function update(Request $request, Campaign $campaign, string $resourceType): RedirectResponse
    {
        Gate::authorize('view', $campaign);

        $type = ResourceType::from($resourceType);

        $validated = $request->validate([
            'count' => 'required|integer|min:0',
        ]);

        $campaign->resources()
            ->where('resource_type', $type->value)
            ->update(['count' => $validated['count']]);

        return back();
    }
}
