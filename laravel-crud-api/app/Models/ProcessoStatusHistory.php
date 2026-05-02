<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcessoStatusHistory extends Model
{
    protected $fillable = [
        'processo_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}