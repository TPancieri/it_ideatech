<?php

use App\Models\Processo;
use App\Models\User;

test('analytics page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $this->get('/analise')->assertOk();
});

