<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escolha extends Model
{
    protected $table = 'escolhas';

    protected $fillable = [
        'inscricao_id',
        'destino_id',
        'ordem',
    ];

    public function inscricao(): BelongsTo {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

      public function destino(): BelongsTo {
        return $this->belongsTo(Destino::class, 'destino_id');
    }

}
