<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cliente extends Model
{
    use HasFactory;
    // add Info para signatarios 
    protected $fillable = ['name', 'email', 'role', 'sector', 'status'];

    public function processos(): BelongsToMany
    {
        return $this->belongsToMany(Processo::class, 'cliente_processo')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('processos.id');
    }

}


