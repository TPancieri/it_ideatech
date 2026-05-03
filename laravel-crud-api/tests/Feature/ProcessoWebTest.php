<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoAssinaturaToken;
use App\Models\User;

test('authenticated user can open processos create form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('processos.create'))->assertOk();
});

test('authenticated user can create processo via web form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $c = Cliente::query()->create([
        'name' => 'S',
        'email' => 'sig_web_proc@example.com',
        'role' => 'R',
        'sector' => 'S',
        'status' => 'active',
    ]);

    $this->post(route('processos.store'), [
        'title' => 'Processo web',
        'description' => 'Desc',
        'category' => 'Financeiro',
        'signatarios' => [(string) $c->id],
    ])->assertRedirect(route('processos.index'));

    $p = Processo::query()->where('title', 'Processo web')->first();
    expect($p)->not->toBeNull()
        ->and($p->responsible_user_id)->toBe($user->id)
        ->and($p->signatarios()->count())->toBe(1)
        ->and(ProcessoAssinaturaToken::query()->where('processo_id', $p->id)->count())->toBe(1);
});

test('creating processo with sem_convites skips invite jobs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $c = Cliente::query()->create([
        'name' => 'S2',
        'email' => 'sig_web_proc2@example.com',
        'role' => 'R',
        'sector' => 'S',
        'status' => 'active',
    ]);

    $this->post(route('processos.store'), [
        'title' => 'Sem convite',
        'description' => 'Desc',
        'category' => 'Financeiro',
        'signatarios' => [(string) $c->id],
        'sem_convites' => '1',
    ])->assertRedirect(route('processos.index'));

    $p = Processo::query()->where('title', 'Sem convite')->first();
    expect($p)->not->toBeNull()
        ->and(ProcessoAssinaturaToken::query()->where('processo_id', $p->id)->count())->toBe(0);
});
