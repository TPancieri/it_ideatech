<?php

use App\Models\Processo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('cannot jump from pending directly to approved', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $processo = Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ]);

    $response = $this->putJson("/api/processo/{$processo->id}", [
        'title' => 'P',
        'description' => 'D',
        'status' => 'approved',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(422);
});

test('can move pending -> in_approval -> approved', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $processo = Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ]);

    $this->putJson("/api/processo/{$processo->id}", [
        'title' => 'P',
        'description' => 'D',
        'status' => 'in_approval',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ], [
        'Accept' => 'application/json',
    ])->assertOk();

    $processo->refresh();
    expect($processo->status)->toBe('in_approval');

    $this->putJson("/api/processo/{$processo->id}", [
        'title' => 'P',
        'description' => 'D',
        'status' => 'approved',
        'responsible_user_id' => $user->id,
        'category' => 'C',
    ], [
        'Accept' => 'application/json',
    ])->assertOk();

    $processo->refresh();
    expect($processo->status)->toBe('approved');
});
