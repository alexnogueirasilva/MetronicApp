<?php declare(strict_types = 1);

use App\Models\{Finance\Account, Tenant, User};

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    actingAs($user);
});

it('should be able to list accounts', function () {
    $account = Account::factory(10)->create();

    $response = getJson(route('finance.accounts.index'));

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'currency',
                    'balance',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
