<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarAnaliseInscricaoRequest;
use App\Http\Requests\AtualizarConferenciaRequest;
use App\Http\Requests\CriarInscricaoRequest;
use App\Http\Resources\ConferenciaResource;
use App\Http\Resources\InscricaoResource;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Services\InscricaoService;
use Illuminate\Http\Request;

class InscricaoController extends Controller
{
    /**
     * Lista as inscrições de um edital.
     * Somente usuários da SME podem realizar essa operação.
     */
    public function index(
        Edital $edital,
        InscricaoService $inscricaoService
    ) {
        $inscricoes = $inscricaoService->listarPorEdital($edital->id);

        return InscricaoResource::collection($inscricoes);
    }

    /**
     * Cria uma nova inscrição.
     * Somente militares vinculados a um cadastro funcional podem se inscrever.
     */
    public function store(
        CriarInscricaoRequest $request,
        Edital $edital,
        InscricaoService $inscricaoService
    ) {
        $usuario = $request->user();

        $militar = $usuario->militar;

        if (! $militar) {
            return response()->json([
                'message' => 'A conta não está vinculada a um cadastro de militar.',
            ], 403);
        }

        $inscricao = $inscricaoService->criar(
            $request->validated(),
            $edital,
            $militar,
            (string) $usuario->id
        );

        $inscricao->load([
            'militar.opmOrigem',
            'edital',
            'escolhas.destino.opm',
            'conferencias.requisito',
        ]);

        return (new InscricaoResource($inscricao))
            ->additional([
                'mensagem' => 'Inscrição criada com sucesso.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consulta uma inscrição específica.
     * Militares podem consultar somente suas próprias inscrições.
     * Usuários da SME podem consultar qualquer inscrição.
     */
    public function show(Request $request, Inscricao $inscricao)
    {
        $usuario = $request->user();

        if ($usuario->perfil_acesso === 'MILITAR') {
            $militar = $usuario->militar;

            if (! $militar) {
                return response()->json([
                    'message' => 'A conta não está vinculada a um cadastro de militar.',
                ], 403);
            }

            if ($inscricao->militar_id !== $militar->id) {
                return response()->json([
                    'message' => 'Você não tem permissão para visualizar esta inscrição.',
                ], 403);
            }
        }
        $inscricao->load([
            'militar.opmOrigem',
            'edital',
            'escolhas.destino.opm',
            'conferencias.requisito',
        ]);

        return new InscricaoResource($inscricao);
    }

    /**
     * Atualiza uma conferência.
     * Somente usuários da SME podem executar essa operação.
     */
    public function atualizarConferencia(
        AtualizarConferenciaRequest $request,
        Inscricao $inscricao,
        int $conferencia,
        InscricaoService $inscricaoService
    ) {
        $usuario = $request->user();

        $conferenciaAtualizada = $inscricaoService->atualizarConferencia(
            $inscricao->id,
            $conferencia,
            $request->validated(),
            (string) $usuario->id
        );

        $conferenciaAtualizada->load('requisito');

        return new ConferenciaResource($conferenciaAtualizada);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Atualiza a situação da análise de uma inscrição.
     * Somente usuários da SME podem executar essa operação.
     */
    public function atualizarAnalise(
        AtualizarAnaliseInscricaoRequest $request,
        Inscricao $inscricao,
        InscricaoService $inscricaoService
    ) {
        $usuario = $request->user();

        $inscricaoAtualizada = $inscricaoService->atualizarAnalise(
            $inscricao->id,
            $request->validated()['situacao'],
            (string) $usuario->id
        );

        $inscricaoAtualizada->load([
            'militar.opmOrigem',
            'edital',
            'escolhas.destino.opm',
            'conferencias.requisito',
        ]);

        return new InscricaoResource($inscricaoAtualizada);
    }
}
