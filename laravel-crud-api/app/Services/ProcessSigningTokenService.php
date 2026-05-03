<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoAssinaturaToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class ProcessSigningTokenService
{
    public function issue(Processo $processo, Cliente $cliente, int $ttlHours = 72): array
    {
        $plain = Str::random(48);
        $hash = hash('sha256', $plain);

        ProcessoAssinaturaToken::query()->create([
            'processo_id' => $processo->id,
            'cliente_id' => $cliente->id,
            'token_hash' => $hash,
            'invite_plain_ciphertext' => Crypt::encryptString($plain),
            'expires_at' => now()->addHours($ttlHours),
        ]);

        return [
            'plain_token' => $plain,
        ];
    }

    public function findValid(string $plainToken): ?ProcessoAssinaturaToken
    {
        $hash = hash('sha256', $plainToken);

        $token = ProcessoAssinaturaToken::query()
            ->where('token_hash', $hash)
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->consumed_at) {
            return null;
        }

        if ($token->expires_at->isPast()) {
            return null;
        }

        return $token;
    }

    public function consume(ProcessoAssinaturaToken $token): void
    {
        $token->consumed_at = now();
        $token->save();
    }
}
