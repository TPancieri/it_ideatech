<?php

use App\Models\Processo;
use App\Models\User;

test('dashboard loads', function () {
    $user = User::factory()->create();

    Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $this->get('/dashboard')->assertOk();
});
