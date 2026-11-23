<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistoricoResource;
use App\Models\Edital;
use App\Services\HistoricoService;
use Illuminate\Http\Request;

class HistoricoController extends Controller
{
    /**
     * Lista o histórico de um edital específico.
     */
    public function index(
        Request $request,
        Edital $edital,
        HistoricoService $historicoService
    ) {
        $usuario = $request->user();

        if ($usuario->perfil_acesso !== 'SME') {
            return response()->json([
                'message' => 'Acesso não autorizado.',
            ], 403);
        }

        $historicos = $historicoService->listarPorEdital(
            $edital->id
        );

        return HistoricoResource::collection($historicos);
    }
}
