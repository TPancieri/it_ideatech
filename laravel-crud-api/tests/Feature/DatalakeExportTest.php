<?php

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoResposta;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

test('datalake export generates files and analytics fact table', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'email' => 'resp_datalake@example.com',
    ]);

    $processo = Processo::query()->create([
        'title' => 'Proc DL',
        'description' => 'D',
        'status' => 'in_approval',
        'responsible_user_id' => $user->id,
        'category' => 'Financeiro',
        'document_path' => 'processos/x.pdf',
    ]);

    $cliente = Cliente::query()->create([
        'name' => 'Signatário DL',
        'email' => 'sig_datalake@example.com',
        'role' => 'Analista',
        'sector' => 'TI',
        'status' => 'active',
    ]);

    $processo->signatarios()->attach($cliente->id, ['sort_order' => 1]);

    $now = now();

    DB::table('processo_assinatura_tokens')->insert([
        'processo_id' => $processo->id,
        'cliente_id' => $cliente->id,
        'token_hash' => hash('sha256', 'tok-test'),
        'expires_at' => $now->copy()->addDay(),
        'consumed_at' => $now,
        'created_at' => $now->copy()->subHour(),
        'updated_at' => $now->copy()->subHour(),
    ]);

    ProcessoResposta::query()->create([
        'processo_id' => $processo->id,
        'cliente_id' => $cliente->id,
        'tipo' => 'approved',
        'justificativa' => null,
        'ip' => '127.0.0.1',
        'user_agent' => 'tests',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $exit = Artisan::call('datalake:export', [
        '--format' => 'jsonl',
        '--name' => 'facts_test',
    ]);

    expect($exit)->toBe(0);

    $disk = Storage::disk('local');
    expect($disk->exists('datalake/facts_test.jsonl'))->toBeTrue();

    $jsonl = $disk->get('datalake/facts_test.jsonl');
    $line = trim(explode("\n", trim($jsonl))[0] ?? '');
    expect($line)->not->toBe('');

    $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

    expect($row['processo_id'])->toBe($processo->id)
        ->and($row['titulo'])->toBe('Proc DL')
        ->and($row['categoria'])->toBe('Financeiro')
        ->and($row['signatario_email'])->toBe('sig_datalake@example.com')
        ->and($row['tipo_resposta'])->toBe('approved')
        ->and($row['convites_enviados'])->toBe(1)
        ->and($row['responsible_user_email'])->toBe('resp_datalake@example.com');

    expect(DB::table('processo_analytics_facts')->count())->toBe(1);
});
