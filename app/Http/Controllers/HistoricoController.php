<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistoricoResource;
use App\Models\Edital;
use App\Services\HistoricoService;

class HistoricoController extends Controller
{
    /**
     * Lista o histórico de um edital específico.
     */
    public function index(
        Edital $edital,
        HistoricoService $historicoService
    ) {
        $historicos = $historicoService->listarPorEdital(
            $edital->id
        );

        return HistoricoResource::collection($historicos);
    }
}
