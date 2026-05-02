<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;

test('can attach and list signatarios on a processo', function () {
    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'Processo X',
        'description' => 'Descrição',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $c1 = Cliente::query()->create([
        'name' => 'Sig 1',
        'email' => 'sig1@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $c2 = Cliente::query()->create([
        'name' => 'Sig 2',
        'email' => 'sig2@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $this->postJson("/api/processo/{$processo->id}/signatarios", [
        'cliente_id' => $c1->id,
    ], [
        'Accept' => 'application/json',
    ])->assertCreated();

    $this->postJson("/api/processo/{$processo->id}/signatarios", [
        'cliente_id' => $c2->id,
        'sort_order' => 1,
    ], [
        'Accept' => 'application/json',
    ])->assertCreated();

    $this->getJson("/api/processo/{$processo->id}/signatarios")
        ->assertOk()
        ->assertJsonCount(2);
});

test('cannot attach the same signatario twice', function () {
    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'Processo X',
        'description' => 'Descrição',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $c1 = Cliente::query()->create([
        'name' => 'Sig 1',
        'email' => 'sig3@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $this->postJson("/api/processo/{$processo->id}/signatarios", [
        'cliente_id' => $c1->id,
    ])->assertCreated();

    $this->postJson("/api/processo/{$processo->id}/signatarios", [
        'cliente_id' => $c1->id,
    ])->assertStatus(422);
});
