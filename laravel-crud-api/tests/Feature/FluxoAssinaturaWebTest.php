<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;
use App\Services\ProcessSigningTokenService;

test('guest cannot open fluxo assinatura index', function () {
    $this->get(route('fluxo.index'))->assertRedirect();
});

test('responsible user can open fluxo show', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $c = Cliente::query()->create([
        'name' => 'Signatário F',
        'email' => 'fluxo_f@example.com',
        'role' => 'R',
        'sector' => 'S',
        'status' => 'active',
    ]);

    $processo = Processo::query()->create([
        'title' => 'P fluxo',
        'description' => '',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Financeiro',
        'document_path' => null,
    ]);
    $processo->signatarios()->attach($c->id, ['sort_order' => 0]);

    $this->actingAs($user);
    $this->get(route('fluxo.show', $processo))->assertOk();

    $this->actingAs($other);
    $this->get(route('fluxo.show', $processo))->assertForbidden();
});

test('responsible user can reveal signing url for active token with ciphertext', function () {
    $user = User::factory()->create();

    $c = Cliente::query()->create([
        'name' => 'Signatário R',
        'email' => 'fluxo_r@example.com',
        'role' => 'R',
        'sector' => 'S',
        'status' => 'active',
    ]);

    $processo = Processo::query()->create([
        'title' => 'P revelar',
        'description' => '',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Financeiro',
        'document_path' => null,
    ]);
    $processo->signatarios()->attach($c->id, ['sort_order' => 0]);

    app(ProcessSigningTokenService::class)->issue($processo, $c, 72);
    $token = $processo->fresh()->assinaturaTokens()->firstOrFail();

    $this->actingAs($user);
    $this->post(route('fluxo.revelar', [$processo, $token]))
        ->assertRedirect()
        ->assertSessionHas('assinatura_url_unica')
        ->assertSessionHas('assinatura_link_revelado', true);
});
