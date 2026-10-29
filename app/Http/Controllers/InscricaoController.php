<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CriarInscricaoRequest;
use App\Models\Edital;
use App\Models\Militar;
use App\Services\InscricaoService;
use App\Http\Resources\InscricaoResource;
use App\Models\Inscricao;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\AtualizarConferenciaRequest;
use App\Http\Resources\ConferenciaResource;
use App\Http\Requests\AtualizarAnaliseInscricaoRequest;

class InscricaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        Edital $edital,
        InscricaoService $inscricaoService
    ): AnonymousResourceCollection {
        $inscricoes = $inscricaoService->listarPorEdital($edital->id);

        return InscricaoResource::collection($inscricoes);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(
        CriarInscricaoRequest $request,
        Edital $edital,
        InscricaoService $inscricaoService
    ) {
        $militar = Militar::findOrFail(4);

        $inscricao = $inscricaoService->criar(
            $request->validated(),
            $edital,
            $militar
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
     * Display the specified resource.
     */
    public function show(Inscricao $inscricao)
    {
        $inscricao->load([
            'militar.opmOrigem',
            'edital',
            'escolhas.destino.opm',
            'conferencias.requisito',
        ]);

        return new InscricaoResource($inscricao);
    }


    public function atualizarConferencia(
        AtualizarConferenciaRequest $request,
        Inscricao $inscricao,
        int $conferencia,
        InscricaoService $inscricaoService
    ): ConferenciaResource {
        $conferenciaAtualizada = $inscricaoService->atualizarConferencia(
            $inscricao->id,
            $conferencia,
            $request->validated()
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

    public function atualizarAnalise(
        AtualizarAnaliseInscricaoRequest $request,
        Inscricao $inscricao,
        InscricaoService $inscricaoService
    ): InscricaoResource {
        $inscricaoAtualizada = $inscricaoService->atualizarAnalise(
            $inscricao->id,
            $request->validated()['situacao']
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
