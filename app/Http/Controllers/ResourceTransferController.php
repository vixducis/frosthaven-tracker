<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ResourceTransferController extends Controller
{
    public function store(Request $request, Campaign $campaign, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $request->validate([
            'transfers' => 'required|array',
            'transfers.*.resource_type' => 'required|string',
            'transfers.*.amount' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($campaign, $character, $validated) {
            foreach ($validated['transfers'] as $transfer) {
                $type = ResourceType::from($transfer['resource_type']);
                $amount = $transfer['amount'];

                $characterResource = $character->resources()
                    ->where('resource_type', $type->value)
                    ->firstOrFail();

                if ($characterResource->count < $amount) {
                    abort(422, "Not enough {$type->label()} to transfer.");
                }

                $characterResource->decrement('count', $amount);

                $campaign->resources()
                    ->where('resource_type', $type->value)
                    ->increment('count', $amount);
            }
        });

        return back();
    }
}
