<?php declare(strict_types = 1);

test('social routes exist', function () {
    $routes = collect($this->app['router']->getRoutes())->map(function ($route) {
        return [
            'name'    => $route->getName(),
            'uri'     => $route->uri(),
            'methods' => $route->methods(),
        ];
    });

    expect($routes->where('uri', 'v1/auth/social/{provider}/callback')->all())->not->toBeEmpty('A rota de callback não existe')
        ->and($routes->where('uri', 'v1/auth/social/{provider}')->all())->not->toBeEmpty('A rota de redirecionamento não existe');

});
