<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('api login returns bearer token', function () {
    $user = User::factory()->create([
        'email' => 'api_login@example.com',
        'password' => 'secret-password-1',
    ]);

    $res = $this->postJson('/api/login', [
        'email' => 'api_login@example.com',
        'password' => 'secret-password-1',
        'device_name' => 'pest',
    ]);

    $res->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);

    expect($res->json('token_type'))->toBe('Bearer');
    expect($res->json('token'))->not->toBeEmpty();
});

test('api routes require authentication', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('api user endpoint works with bearer token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user')->assertOk()->assertJsonFragment(['email' => $user->email]);
});
