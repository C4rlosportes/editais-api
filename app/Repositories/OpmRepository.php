<?php

namespace App\Repositories;

use App\Models\Opm;
use Illuminate\Database\Eloquent\Collection;

class OpmRepository
{
    /**
     * Lista as OPMs cadastradas, ordenadas pelo nome.
     */
    public function listar(): Collection
    {
        return Opm::query()
            ->orderBy('nome')
            ->get();
    }
}
