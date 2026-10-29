<?php

namespace App\Services;

use App\Repositories\OpmRepository;
use Illuminate\Database\Eloquent\Collection;

class OpmService
{
    public function __construct(
        private OpmRepository $opmRepository
    ) {
    }

    /**
     * Lista as OPMs cadastradas.
     */
    public function listar(): Collection
    {
        return $this->opmRepository->listar();
    }
}
