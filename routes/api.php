<?php

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactListController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Contacts API
Route::prefix('contacts')->group(function () {
    Route::get('/', [ContactController::class, 'index']);
    Route::post('/', [ContactController::class, 'store']);
    Route::post('{contact}/unsubscribe', [ContactController::class, 'unsubscribe']);
});

// Contact Lists API
Route::prefix('contact-lists')->group(function () {
    Route::get('/', [ContactListController::class, 'index']);
    Route::post('/', [ContactListController::class, 'store']);
    Route::post('{contactList}/contacts', [ContactListController::class, 'addContact']);
});

// Campaigns API
Route::prefix('campaigns')->group(function () {
    Route::get('/', [CampaignController::class, 'index']);
    Route::post('/', [CampaignController::class, 'store']);
    Route::get('{campaign}', [CampaignController::class, 'show']);
    Route::post('{campaign}/dispatch', [CampaignController::class, 'dispatch']);
});
