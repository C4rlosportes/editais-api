<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conferencia extends Model
{
      protected $table = 'conferencias';

    const CREATED_AT = null;

    protected $fillable = [
        'inscricao_id',
        'requisito_id',
        'resultado',
        'observacao',
        'conferido_por',
    ];

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(Requisito::class, 'requisito_id');
    }
}
