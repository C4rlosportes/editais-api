<?php

namespace App\Services;

use App\Repositories\HistoricoRepository;
use Illuminate\Database\Eloquent\Collection;

class HistoricoService
{
    public function __construct(
        private HistoricoRepository $historicoRepository
    ) {}

    /**
     * Lista o histórico de um edital.
     */
    public function listarPorEdital(int $editalId): Collection
    {
        return $this->historicoRepository
            ->listarPorEdital($editalId);
    }
}
