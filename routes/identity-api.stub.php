<?php

declare(strict_types=1);

use HiTaqnia\Haykal\Api\Identity\Controllers\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Haykal Identity API
|--------------------------------------------------------------------------
|
| Published by `hitaqnia/haykal-api`. Includes a single `GET /api/identity/me`
| endpoint that returns the currently authenticated Huwiya user. Mount this
| file from `routes/api.php` (or include it directly in `bootstrap/app.php`).
|
| Future profile-mutation endpoints (update, avatar) will be added here as
| the Huwiya integration patterns for them are established.
|
*/

Route::prefix('identity')
    ->middleware(['auth:huwiya-api'])
    ->group(function () {
        Route::get('me', MeController::class)->name('haykal.identity.me');
    });
