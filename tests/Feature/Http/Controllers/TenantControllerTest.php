<?php declare(strict_types = 1);

use App\Enums\PlanType;
use App\Models\{Tenant, User};
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\{assertDatabaseHas, assertDatabaseMissing, deleteJson, getJson, postJson, putJson};

beforeEach(function () {
    // Autenticar o usuário com um tenant
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);
});

it('returns paginated list of tenants', function () {
    // Criar tenants de teste
    Tenant::factory()->count(3)->create();

    // Chamar o endpoint
    $response = getJson(route('tenant.index'));

    // Verificar resposta
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

    // Verificar que a paginação está funcionando corretamente
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(3);
});

it('can filter tenants by name', function () {
    // Criar tenants com nomes específicos
    Tenant::factory()->create(['name' => 'Test Tenant']);
    Tenant::factory()->create(['name' => 'Another Tenant']);
    Tenant::factory()->create(['name' => 'Third Company']);

    // Filtrar por nome
    $response = getJson(route('tenant.index', ['filter' => ['name' => 'Test']]));

    // Verificar que o filtro foi aplicado corretamente
    $response->assertOk();
    expect($response->json('data'))->toBeArray();

    // Verificar se todos os resultados contêm "Test" no nome
    $data = $response->json('data');

    $hasTestInName = collect($data)->every(function ($item) {
        return str_contains($item['name'], 'Test');
    });

    expect($hasTestInName)->toBeTrue();
});

it('can filter tenants by plan', function () {
    // Criar tenants com diferentes planos
    Tenant::factory()->create(['plan' => PlanType::FREE]);
    Tenant::factory()->create(['plan' => PlanType::BASIC]);
    Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);

    // Filtrar por plano
    $response = getJson(route('tenant.index', ['filter' => ['plan' => 'basic']]));

    // Verificar que o filtro foi aplicado corretamente
    $response->assertOk();

    // Verificar se todos os resultados têm plano "basic"
    $data = $response->json('data');

    $allBasicPlan = collect($data)->every(function ($item) {
        return $item['plan'] === 'basic';
    });

    expect($allBasicPlan)->toBeTrue();
});

it('can create a new tenant', function () {
    // Dados para criação do tenant
    $data = [
        'name'      => 'New Test Tenant',
        'domain'    => 'new-test-tenant.com',
        'plan'      => PlanType::BASIC->value,
        'is_active' => true,
    ];

    // Enviar requisição de criação
    $response = postJson(route('tenant.store'), $data);

    // Verificar resposta
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

    // Verificar que o tenant foi criado no banco de dados
    assertDatabaseHas('tenants', [
        'name'   => 'New Test Tenant',
        'domain' => 'new-test-tenant.com',
        'plan'   => PlanType::BASIC->value,
    ]);
});

it('can show tenant details', function () {
    // Criar tenant para testar
    $tenant = Tenant::factory()->create();

    // Obter detalhes do tenant
    $response = getJson(route('tenant.show', $tenant));

    // Verificar resposta
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

    // Verificar que os dados corretos foram retornados
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
    // Criar tenant para testar
    $tenant = Tenant::factory()->create([
        'name' => 'Old Name',
        'plan' => PlanType::BASIC,
    ]);

    // Dados para atualização
    $data = [
        'name' => 'Updated Name',
        'plan' => PlanType::PROFESSIONAL->value,
    ];

    // Enviar requisição de atualização
    $response = putJson(route('tenant.update', $tenant), $data);

    // Verificar resposta
    $response->assertOk();
    $response->assertJson([
        'data' => [
            'name' => 'Updated Name',
            'plan' => PlanType::PROFESSIONAL->value,
        ],
    ]);

    // Verificar que o tenant foi atualizado no banco de dados
    assertDatabaseHas('tenants', [
        'id'   => $tenant->id,
        'name' => 'Updated Name',
        'plan' => PlanType::PROFESSIONAL->value,
    ]);
});

it('can delete a tenant', function () {
    // Criar tenant para testar
    $tenant = Tenant::factory()->create();

    // Enviar requisição de remoção
    $response = deleteJson(route('tenant.destroy', $tenant));

    // Verificar resposta
    $response->assertOk();
    $response->assertJson([
        'message' => 'Tenant excluído com sucesso',
    ]);

    // Verificar que o tenant foi removido do banco de dados
    assertDatabaseMissing('tenants', [
        'id' => $tenant->id,
    ]);
});

it('prevents deletion of tenant with users', function () {
    // Criar tenant e usuário associado
    $tenant = Tenant::factory()->create();
    User::factory()->create(['tenant_id' => $tenant->id]);

    // Tentar remover o tenant
    $response = deleteJson(route('tenant.destroy', $tenant));

    // Verificar erro
    $response->assertStatus(409); // Conflict
    $response->assertJson([
        'message' => 'Não é possível excluir um tenant que possui usuários',
    ]);

    // Verificar que o tenant permanece no banco de dados
    assertDatabaseHas('tenants', [
        'id' => $tenant->id,
    ]);
});
