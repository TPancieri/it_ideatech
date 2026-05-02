<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoResposta extends Model
{
    protected $fillable = [
        'processo_id',
        'cliente_id',
        'tipo',
        'justificativa',
        'ip',
        'user_agent',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
