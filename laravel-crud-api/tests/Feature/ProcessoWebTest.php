<?php

use App\Models\Cliente;
use App\Models\Processo;
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
        ->and($p->signatarios()->count())->toBe(1);
});
