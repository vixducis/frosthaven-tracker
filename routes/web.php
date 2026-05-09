<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignInvitationController;
use App\Http\Controllers\CampaignResourceController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterResourceController;
use App\Http\Controllers\ResourceTransferController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('dashboard', '/campaigns')->name('dashboard');

    Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

    Route::post('campaigns/{campaign}/characters', [CharacterController::class, 'store'])->name('campaigns.characters.store');
    Route::get('campaigns/{campaign}/characters/{character}', [CharacterController::class, 'show'])->name('campaigns.characters.show');
    Route::patch('campaigns/{campaign}/characters/{character}', [CharacterController::class, 'update'])->name('campaigns.characters.update');
    Route::post('campaigns/{campaign}/characters/{character}/retire', [CharacterController::class, 'retire'])->name('campaigns.characters.retire');

    Route::patch('campaigns/{campaign}/resources/{resourceType}', [CampaignResourceController::class, 'update'])->name('campaigns.resources.update');
    Route::patch('campaigns/{campaign}/characters/{character}/resources/{resourceType}', [CharacterResourceController::class, 'update'])->name('campaigns.characters.resources.update');

    Route::post('campaigns/{campaign}/characters/{character}/transfers', [ResourceTransferController::class, 'store'])->name('campaigns.characters.transfers.store');

    Route::post('campaigns/{campaign}/invitations', [CampaignInvitationController::class, 'store'])->name('campaigns.invitations.store');
    Route::get('invitations/{token}', [CampaignInvitationController::class, 'show'])->name('campaigns.invitations.show');
    Route::post('invitations/{token}', [CampaignInvitationController::class, 'update'])->name('campaigns.invitations.update');
});

require __DIR__.'/settings.php';
