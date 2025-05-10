<?php declare(strict_types = 1);

use App\Models\User;

use function Pest\Laravel\{actingAs, getJson};

it('can get authenticated user', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = getJson(route('auth.me'));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id'    => $user->id,
            'email' => $user->email,
        ],
    ]);
});
