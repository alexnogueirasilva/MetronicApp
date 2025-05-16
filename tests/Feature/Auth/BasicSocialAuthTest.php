<?php declare(strict_types = 1);

test('social routes exist', function () {
    $routes = collect($this->app['router']->getRoutes())->map(function ($route) {
        return [
            'name'    => $route->getName(),
            'uri'     => $route->uri(),
            'methods' => $route->methods(),
        ];
    });

    // Verifica se a rota de callback existe
    expect($routes->where('uri', 'api/v1/auth/social/{provider}/callback')->all())->not->toBeEmpty('A rota de callback não existe');

    // Verifica se a rota de redirecionamento existe
    expect($routes->where('uri', 'api/v1/auth/social/{provider}')->all())->not->toBeEmpty('A rota de redirecionamento não existe');
});
