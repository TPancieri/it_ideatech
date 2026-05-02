<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\User;

test('reports pages load', function () {
    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'P',
        'description' => 'D',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $cliente = Cliente::query()->create([
        'name' => 'Sig',
        'email' => 'sig_reports@example.com',
        'role' => 'Cargo',
        'sector' => 'Setor',
        'status' => 'active',
    ]);

    $processo->signatarios()->attach($cliente->id, ['sort_order' => 0]);

    $this->get('/relatorios/status')->assertOk();
    $this->get('/relatorios/produtividade-signatarios')->assertOk();
    $this->get('/relatorios/processos-periodo')->assertOk();
    $this->get('/relatorios/reprovacoes')->assertOk();

    $this->get('/relatorios/status.csv')->assertOk();
    $this->get('/relatorios/produtividade-signatarios.csv')->assertOk();
    $this->get('/relatorios/processos-periodo.csv')->assertOk();
    $this->get('/relatorios/reprovacoes.csv')->assertOk();
});
