<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'status', 'responsible_user_id', 'category', 'document_path'];
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function signatarios(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_processo')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('clientes.id');
    }

    public function assinaturaTokens(): HasMany
    {
        return $this->hasMany(ProcessoAssinaturaToken::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ProcessoStatusHistory::class);
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(ProcessoResposta::class);
    }


}

