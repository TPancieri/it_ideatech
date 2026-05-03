<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoAssinaturaToken;
use App\Models\User;
use App\Services\ProcessSigningTokenService;
use Laravel\Sanctum\Sanctum;

test('convites endpoint sends invite for all signatarios synchronously', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $processo = Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'C',
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

    $processo->signatarios()->attach([
        $c1->id => ['sort_order' => 0],
        $c2->id => ['sort_order' => 0],
    ]);

    $this->postJson("/api/processo/{$processo->id}/convites", [], [
        'Accept' => 'application/json',
    ])->assertStatus(200);

    expect(ProcessoAssinaturaToken::query()->where('processo_id', $processo->id)->count())->toBe(2);
});

test('parallel approvals finalize process', function () {
    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ]);

    $c1 = Cliente::query()->create([
        'name' => 'Sig 1',
        'email' => 'sig10@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $c2 = Cliente::query()->create([
        'name' => 'Sig 2',
        'email' => 'sig11@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $processo->signatarios()->attach([
        $c1->id => ['sort_order' => 0],
        $c2->id => ['sort_order' => 0],
    ]);

    $tokens = app(ProcessSigningTokenService::class);
    $t1 = $tokens->issue($processo, $c1)['plain_token'];
    $t2 = $tokens->issue($processo, $c2)['plain_token'];

    $this->post(route('assinatura.approve', ['token' => $t1]))
        ->assertRedirect();

    $processo->refresh();
    expect($processo->status)->toBe('in_approval');

    $this->post(route('assinatura.approve', ['token' => $t2]))
        ->assertRedirect();

    $processo->refresh();
    expect($processo->status)->toBe('approved');
});
