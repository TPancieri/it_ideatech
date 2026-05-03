<?php

namespace App\Services;

use App\Models\AuditoriaEvento;
use App\Models\Cliente;
use App\Models\Processo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    /**
     * @return array<string, mixed>
     */
    public static function clienteSnapshot(Cliente $cliente): array
    {
        return $cliente->only(['name', 'email', 'role', 'sector', 'status']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function processoSnapshot(Processo $processo): array
    {
        return $processo->only([
            'title',
            'description',
            'status',
            'responsible_user_id',
            'category',
            'document_path',
        ]);
    }

    public static function log(
        string $acao,
        ?Model $subject = null,
        ?Model $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?array $meta = null,
        ?Request $request = null,
    ): void {
        AuditoriaEvento::query()->create([
            'acao' => $acao,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'actor_type' => $actor ? $actor->getMorphClass() : null,
            'actor_id' => $actor?->getKey(),
            'before' => $before,
            'after' => $after,
            'meta' => $meta,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
