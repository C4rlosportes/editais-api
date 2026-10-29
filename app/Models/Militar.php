<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Opm;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Militar extends Model
{
        public function opmOrigem(): BelongsTo {
            return $this->belongsTo(Opm::class, 'opm_origem_id');  #Isso faz com que um (1) militar pertence a uma Opm
        }

        public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class, 'militar_id');
    }

        protected $table = 'militares';

        protected $fillable = [
        'usuario_keycloak',
        'perfil',
        'id_funcional',
        'nome',
        'posto_graduacao',
        'opm_origem_id',
        'municipio',
    ];
}
