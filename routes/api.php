<?php declare(strict_types = 1);

use Illuminate\Support\Facades\{Route};

/*
|--------------------------------------------------------------------------
| API Routes without explicit version
|--------------------------------------------------------------------------
|
| These routes don't have an explicit version in the URL path.
| They will be redirected to the appropriate version based on headers,
| query params, or default configuration.
|
*/

Route::get('/status', fn () => response()->json([
    'status'      => 'online',
    'time'        => now()->toIso8601String(),
    'api_version' => app('api.version'),
]))->name('status');

Route::fallback(function () {
    $version = app('api.version');
    $path    = request()->path();

    return redirect()->to("{$version}/{$path}");
});
