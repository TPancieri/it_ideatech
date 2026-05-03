<?php

use App\Models\AuditoriaEvento;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('auditoria page loads', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('auditoria.index'))->assertOk();
});

test('creating cliente logs audit event', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/cliente', [
        'name' => 'N',
        'email' => 'audit_cli@example.com',
        'role' => 'R',
        'sector' => 'S',
        'status' => 'active',
    ], ['Accept' => 'application/json'])->assertCreated();

    expect(AuditoriaEvento::query()->where('acao', 'cliente.criado')->count())->toBe(1);
});

test('creating processo logs audit event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Sanctum::actingAs($user);

    $this->postJson('/api/processo', [
        'title' => 'T',
        'description' => 'D',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ], ['Accept' => 'application/json'])->assertCreated();

    expect(AuditoriaEvento::query()->where('acao', 'processo.criado')->count())->toBe(1);
});
