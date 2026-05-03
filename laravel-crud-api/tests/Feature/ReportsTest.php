<?php

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('authenticated user can open processos create form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('processos.create'))->assertOk();
});

test('authenticated user can create processo via web form', function () {
    Queue::fake();

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

    Queue::assertPushed(SendProcessSignatureInviteJob::class, 1);
});

test('creating processo with sem_convites skips invite jobs', function () {
    Queue::fake();

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
    expect($p)->not->toBeNull();

    Queue::assertNotPushed(SendProcessSignatureInviteJob::class);
});

test('authenticated user can open edit processo form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $p = Processo::query()->create([
        'title' => 'Editável',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $this->get(route('processos.edit', $p))->assertOk();
});
