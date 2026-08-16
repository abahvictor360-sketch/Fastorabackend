<?php

use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\SeedMediaFallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rendered directly (not under /api) — clicked from a mail client with no
// frontend app behind it, so it needs to work as a plain browser page.
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

// Only reached when the file is missing from the public disk — the .htaccess
// rule in front of this serves it directly whenever it is there. Restores the
// bundled original so a deploy that skipped app:sync-media cannot take every
// image on the site down at once.
Route::get('/storage/{path}', SeedMediaFallbackController::class)
    ->where('path', '.*')
    ->name('storage.fallback');
