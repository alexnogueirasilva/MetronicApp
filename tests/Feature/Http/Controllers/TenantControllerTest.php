<?php declare(strict_types = 1);

use App\Enums\PlanType;
use App\Models\{Tenant, User};
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\{assertDatabaseHas, assertDatabaseMissing, deleteJson, getJson, postJson, putJson};

beforeEach(function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);
});

it('returns paginated list of tenants', function () {
    Tenant::factory()->count(3)->create();

    $response = getJson(route('tenant.index'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'name',
                'domain',
                'plan',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ],
        'meta' => [
            'current_page',
            'from',
            'last_page',
            'per_page',
            'to',
            'total',
        ],
    ]);

    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(3);
});

it('can filter tenants by name', function () {
    Tenant::factory()->create(['name' => 'Test Tenant']);
    Tenant::factory()->create(['name' => 'Another Tenant']);
    Tenant::factory()->create(['name' => 'Third Company']);

    $response = getJson(route('tenant.index', ['filter' => ['name' => 'Test']]));

    $response->assertOk();
    expect($response->json('data'))->toBeArray();

    $data = $response->json('data');

    $hasTestInName = collect($data)->every(function ($item) {
        return str_contains($item['name'], 'Test');
    });

    expect($hasTestInName)->toBeTrue();
});

it('can filter tenants by plan', function () {
    Tenant::factory()->create(['plan' => PlanType::FREE]);
    Tenant::factory()->create(['plan' => PlanType::BASIC]);
    Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);

    $response = getJson(route('tenant.index', ['filter' => ['plan' => 'basic']]));

    $response->assertOk();

    $data = $response->json('data');

    $allBasicPlan = collect($data)->every(function ($item) {
        return $item['plan'] === 'basic';
    });

    expect($allBasicPlan)->toBeTrue();
});

it('can create a new tenant', function () {
    $data = [
        'name'      => 'New Test Tenant',
        'domain'    => 'new-test-tenant.com',
        'plan'      => PlanType::BASIC->value,
        'is_active' => true,
    ];

    $response = postJson(route('tenant.store'), $data, ['Idempotency-Key' => Str::uuid()]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'domain',
            'plan',
            'is_active',
            'created_at',
            'updated_at',
        ],
    ]);

    assertDatabaseHas('tenants', [
        'name'   => 'New Test Tenant',
        'domain' => 'new-test-tenant.com',
        'plan'   => PlanType::BASIC->value,
    ]);
});

it('can show tenant details', function () {
    $tenant = Tenant::factory()->create();

    $response = getJson(route('tenant.show', $tenant));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'domain',
            'plan',
            'is_active',
            'created_at',
            'updated_at',
        ],
    ]);

    $response->assertJson([
        'data' => [
            'id'     => $tenant->id,
            'name'   => $tenant->name,
            'domain' => $tenant->domain,
            'plan'   => $tenant->plan->value,
        ],
    ]);
});

it('can update a tenant', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Old Name',
        'plan' => PlanType::BASIC,
    ]);

    $data = [
        'name' => 'Updated Name',
        'plan' => PlanType::PROFESSIONAL->value,
    ];

    $response = putJson(route('tenant.update', $tenant), $data, ['Idempotency-Key' => Str::uuid()]);

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'name' => 'Updated Name',
            'plan' => PlanType::PROFESSIONAL->value,
        ],
    ]);

    assertDatabaseHas('tenants', [
        'id'   => $tenant->id,
        'name' => 'Updated Name',
        'plan' => PlanType::PROFESSIONAL->value,
    ]);
});

it('can delete a tenant', function () {
    $tenant = Tenant::factory()->create();

    $response = deleteJson(route('tenant.destroy', $tenant), [], ['Idempotency-Key' => Str::uuid()]);

    $response->assertOk();
    $response->assertJson([
        'message' => 'Tenant excluído com sucesso',
    ]);

    assertDatabaseMissing('tenants', [
        'id' => $tenant->id,
    ]);
});

it('prevents deletion of tenant with users', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['tenant_id' => $tenant->id]);

    $response = deleteJson(route('tenant.destroy', $tenant), [], ['Idempotency-Key' => Str::uuid()]);

    $response->assertStatus(409); // Conflict
    $response->assertJson([
        'message' => 'Não é possível excluir um tenant que possui usuários',
    ]);

    assertDatabaseHas('tenants', [
        'id' => $tenant->id,
    ]);
});
