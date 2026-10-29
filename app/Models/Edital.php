<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edital extends Model
{
    protected $table = 'editais';

    protected $fillable = [
        'codigo',
        'titulo',
        'descricao',
        'max_opcoes',
        'situacao',
    ];

    public function requisitos(): HasMany
    {
        return $this->hasMany(Requisito::class, 'edital_id');
    }

    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class, 'edital_id');
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class, 'edital_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(Historico::class, 'edital_id');
    }
}
