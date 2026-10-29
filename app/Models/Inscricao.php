<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscricao extends Model
{
    protected $table = 'inscricoes';

    protected $fillable = [
        'protocolo',
        'edital_id',
        'militar_id',
        'situacao',
        'aceite_condicoes',
        'version',
        'submitted_at',
        'concluded_at',
    ];

     protected $casts = [
        'aceite_condicoes' => 'boolean',
        'version' => 'integer',
        'submitted_at' => 'datetime',
        'concluded_at' => 'datetime',
    ];

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class, 'edital_id');
    }

    public function militar(): BelongsTo
    {
        return $this->belongsTo(Militar::class, 'militar_id');
    }

    public function escolhas(): HasMany
    {
        return $this->hasMany(Escolha::class, 'inscricao_id');
    }

    public function conferencias(): HasMany
    {
        return $this->hasMany(Conferencia::class, 'inscricao_id');
    }
}
