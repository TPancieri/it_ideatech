<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoAnalyticsFact extends Model
{
    protected $table = 'processo_analytics_facts';

    protected $fillable = [
        'processo_id',
        'processo_title',
        'processo_category',
        'processo_status',
        'processo_created_at',
        'processo_updated_at',
        'document_path',
        'responsible_user_id',
        'responsible_user_email',
        'signatario_id',
        'signatario_nome',
        'signatario_email',
        'signatario_funcao',
        'signatario_setor',
        'signatario_status',
        'sort_order',
        'convite_primeiro_envio_em',
        'convite_ultimo_envio_em',
        'convites_enviados',
        'tipo_resposta',
        'resposta_em',
        'tempo_resposta_horas',
        'justificativa_reprovacao',
    ];

    protected function casts(): array
    {
        return [
            'processo_created_at' => 'datetime',
            'processo_updated_at' => 'datetime',
            'convite_primeiro_envio_em' => 'datetime',
            'convite_ultimo_envio_em' => 'datetime',
            'resposta_em' => 'datetime',
            'tempo_resposta_horas' => 'float',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function signatario(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'signatario_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
