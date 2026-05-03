<?php

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;
use App\Services\ProcessSigningTokenService;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('enqueue invitation jobs for all signatarios', function () {
    Queue::fake();

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
    ])->assertStatus(202);

    Queue::assertPushed(SendProcessSignatureInviteJob::class, 2);
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
