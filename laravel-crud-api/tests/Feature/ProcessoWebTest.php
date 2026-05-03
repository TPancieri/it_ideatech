<?php

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

test('responsible can stream processo document via web route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('processos/documents/x.pdf', '%PDF-1.4 demo');

    $user = User::factory()->create();
    $this->actingAs($user);

    $p = Processo::query()->create([
        'title' => 'Com doc',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
        'document_path' => 'processos/documents/x.pdf',
    ]);

    $this->get(route('processos.documento', $p))->assertOk();
});

test('other user cannot stream processo document', function () {
    Storage::fake('public');
    Storage::disk('public')->put('processos/documents/y.pdf', '%PDF-1.4 demo');

    $owner = User::factory()->create();
    $other = User::factory()->create();

    $p = Processo::query()->create([
        'title' => 'Privado',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $owner->id,
        'category' => 'Cat',
        'document_path' => 'processos/documents/y.pdf',
    ]);

    $this->actingAs($other);
    $this->get(route('processos.documento', $p))->assertForbidden();
});

test('authenticated user can trigger demo seed from painel in testing env', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->from(route('painel'))
        ->post(route('painel.demo-seed'))
        ->assertRedirect(route('painel'));

    expect(
        Processo::query()
            ->where('responsible_user_id', $user->id)
            ->where('title', 'like', '[Demo] %')
            ->count()
    )->toBe(40);
});
