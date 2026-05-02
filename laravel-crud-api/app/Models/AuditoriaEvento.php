<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditoriaEvento extends Model
{
    protected $fillable = [
        'acao',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'before',
        'after',
        'meta',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'meta' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
