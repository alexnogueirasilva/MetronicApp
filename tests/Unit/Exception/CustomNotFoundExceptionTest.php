<?php declare(strict_types = 1);

use App\Exceptions\CustomNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log};
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('it parses known NotFoundHttpException message correctly', function (): void {
    $originalMessage   = 'No query results for model [App\\Models\\User] 123';
    $notFoundException = new NotFoundHttpException($originalMessage);

    $custom = CustomNotFoundException::fromNotFoundHttpException($notFoundException);

    expect($custom)->toBeInstanceOf(CustomNotFoundException::class)
        ->and($custom->getMessage())->toBe('Recurso não encontrado')
        ->and($custom->developerMessage)->toBe('Nenhum resultado encontrado para o modelo App\\Models\\User com ID 123');
});

test('it uses default message for unknown NotFoundHttpException message', function (): void {
    $originalMessage   = 'Some other not found error';
    $notFoundException = new NotFoundHttpException($originalMessage);

    $custom = CustomNotFoundException::fromNotFoundHttpException($notFoundException);

    expect($custom->developerMessage)->toBe($originalMessage);
});

test('it renders correct json response in local environment', function (): void {
    config()->set('app.env', 'local');

    $exception = new NotFoundHttpException('No query results for model [App\\Models\\User] 123');
    $custom    = CustomNotFoundException::fromNotFoundHttpException($exception);
    $request   = Request::create('/test', 'GET');

    $response = TestResponse::fromBaseResponse($custom->render($request));

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message'           => 'Recurso não encontrado',
        'developer_message' => 'Nenhum resultado encontrado para o modelo App\\Models\\User com ID 123',
        'exception'         => NotFoundHttpException::class,
    ]);
});

test('it logs in production environment', function (): void {
    config()->set('app.env', 'production');

    Log::shouldReceive('warning')->once();

    $exception = new NotFoundHttpException('No query results for model [App\\Models\\User] 123');
    $custom    = CustomNotFoundException::fromNotFoundHttpException($exception);
    $request   = Request::create('/test', 'GET');

    $response = TestResponse::fromBaseResponse($custom->render($request));

    $response->assertStatus(404);
    $response->assertJsonMissing(['developer_message']);
});
