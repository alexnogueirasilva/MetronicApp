<?php declare(strict_types = 1);

use App\Models\{Finance\Account, Tenant, User};

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function () {
    global $user, $tenant;
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    actingAs($user);
});

it('should be able to list accounts', function () {
    Account::factory(10)->create();

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

it('should be able to filter account', function () {
    global $user;

    Account::factory(10)->create([
        'tenant_id' => $user->tenant_id,
    ]);
    Account::factory()->create([
        'name'      => 'Account 1',
        'tenant_id' => $user->tenant_id,
    ]);

    $response = getJson(route('finance.accounts.index', [
        'filter' => [
            'name' => 'Account 1',
        ],
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
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
