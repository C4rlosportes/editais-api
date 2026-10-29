<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destino extends Model
{
    protected $table = 'destinos';

    protected $fillable = [
        'edital_id',
        'opm_id',
        'vagas',
    ];

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class, 'edital_id');
    }

    public function opm(): BelongsTo
    {
        return $this->belongsTo(Opm::class, 'opm_id');
    }

    public function escolhas(): HasMany
    {
        return $this->hasMany(Escolha::class, 'destino_id');
    }
}
