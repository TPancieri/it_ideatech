<?php

namespace App\Services;

use App\Models\AuditoriaEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
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
