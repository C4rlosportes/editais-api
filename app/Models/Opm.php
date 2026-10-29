<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;  #Cria a Entidade Opm
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opm extends Model
{
    public function militares(): HasMany {
        return $this->hasMany(Militar::class, 'opm_origem_id'); #Isso faz com que uma (1) Opm pertence a vários militares
    }

    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class, 'opm_id');
    }

    protected $fillable = [  #O fillable serve para dizer ao model quais campos podem ser preenchidos na tabela
        'codigo',
        'nome',
        'municipio',
    ];
}
