<?php

use App\Models\Processo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('user cannot view another users processo via api', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'Privado',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $owner->id,
        'category' => 'C',
    ]);

    Sanctum::actingAs($intruder);

    $this->getJson("/api/processo/{$processo->id}", ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('user cannot create processo for another responsible_user_id', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Sanctum::actingAs($owner);

    $this->postJson('/api/processo', [
        'title' => 'X',
        'description' => 'D',
        'responsible_user_id' => $other->id,
        'category' => 'C',
    ], ['Accept' => 'application/json'])->assertStatus(422);
});
