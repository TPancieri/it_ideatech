<?php

namespace App\Services;

use App\Models\Processo;
use App\Models\ProcessoStatusHistory;
use Illuminate\Database\Eloquent\Model;

final class StatusTransitionLogger
{
    public static function record(
        Processo $processo,
        ?string $from,
        string $to,
        ?Model $actor = null,
        ?string $reason = null,
        ?array $meta = null,
    ): void {
        ProcessoStatusHistory::query()->create([
            'processo_id' => $processo->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actor ? $actor->getMorphClass() : null,
            'actor_id' => $actor?->getKey(),
            'reason' => $reason,
            'meta' => $meta,
        ]);
    }
}
