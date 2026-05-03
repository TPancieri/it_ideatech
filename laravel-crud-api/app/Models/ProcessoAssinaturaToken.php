<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoAssinaturaToken extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = [
        'invite_plain_ciphertext',
    ];

    protected $fillable = [
        'processo_id',
        'cliente_id',
        'token_hash',
        'invite_plain_ciphertext',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
