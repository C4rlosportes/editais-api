<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisito extends Model
{
    protected $table = 'requisitos';

    protected $fillable = [
        'edital_id',
        'descricao',
        'ordem',
    ];

    public function edital(): BelongsTo {
        return $this->belongsTo(Edital::class, 'edital_id');
    }

    public function conferencias(): HasMany
    {
        return $this->hasMany(Conferencia::class, 'requisito_id');
    }
}
