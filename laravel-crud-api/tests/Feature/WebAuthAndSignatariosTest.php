<?php

use App\Models\User;

test('guest is redirected from painel to login route then home', function () {
    $this->get(route('painel'))->assertRedirect(route('login'));
});

test('user can register and reach painel', function () {
    $this->post('/register', [
        'name' => 'Operador',
        'email' => 'op_web@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
    ])->assertRedirect(route('painel'));

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'op_web@example.com')->exists())->toBeTrue();
});

test('authenticated user can open signatarios index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('signatarios.index'))->assertOk();
});
