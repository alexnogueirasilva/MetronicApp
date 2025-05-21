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

Route::get('/status', static fn () => response()->json([
    'status'      => 'online',
    'time'        => now()->toIso8601String(),
    'api_version' => app('api.version'),
]))->name('status');

Route::fallback(static function () {
    $version = app('api.version');
    $path    = request()->path();

    if (!str_starts_with($path, 'api')) {
        return abort(404);
    }

    // Remove o prefixo 'api/' do path, se existir
    if (str_starts_with($path, 'api/')) {
        $path = substr($path, 4);
    }

    if (preg_match('/^v\d+/', $path)) {
        return abort(404, 'API endpoint not found');
    }

    if ($path === '' || $path === '0' || $path === 'api') {
        return redirect()->to("api/{$version}/status");
    }

    return redirect()->to("api/{$version}/{$path}");
});
