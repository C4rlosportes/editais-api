<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historico extends Model
{
    protected $table = 'historicos';

    const UPDATED_AT = null;

    protected $fillable = [
        'edital_id',
        'acao',
        'autor',
        'dados_anteriores',
        'dados_posteriores',
    ];

    protected $casts = [
        'dados_anteriores' => 'array',
        'dados_posteriores' => 'array',
    ];

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class, 'edital_id');
    }
}
