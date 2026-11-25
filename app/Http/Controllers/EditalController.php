<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarEditalRequest;
use App\Http\Requests\AtualizarSituacaoEditalRequest;
use App\Http\Requests\CriarEditalRequest;
use App\Http\Resources\EditalResource;
use App\Models\Edital;
use App\Services\EditalService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EditalController extends Controller
{
    /**
     * Cria um edital com seus requisitos e destinos.
     */
    public function store(
        CriarEditalRequest $request,
        EditalService $editalService
    ) {
        $usuario = $request->user();

        $edital = $editalService->criar(
            $request->validated(),
            (string) $usuario->id
        );

        return (new EditalResource($edital))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Lista os editais cadastrados.
     */
    public function index(
        EditalService $editalService
    ): AnonymousResourceCollection {
        $editais = $editalService->listar();

        return EditalResource::collection($editais);
    }

    /**
     * Consulta um edital específico.
     */
    public function show(
        int $edital,
        EditalService $editalService
    ): EditalResource {
        $editalEncontrado = $editalService->buscarPorId($edital);

        return new EditalResource($editalEncontrado);
    }

    /**
     * Atualiza o conteúdo de um edital em RASCUNHO.
     */
    public function atualizarConteudo(
        AtualizarEditalRequest $request,
        Edital $edital,
        EditalService $editalService
    ) {
        $usuario = $request->user();

        $editalAtualizado = $editalService->atualizarConteudo(
            $edital,
            $request->validated(),
            (string) $usuario->id
        );

        return new EditalResource($editalAtualizado);
    }

    public function update(
        AtualizarSituacaoEditalRequest $request,
        Edital $edital,
        EditalService $editalService
    ) {
        $usuario = $request->user();

        $editalAtualizado = $editalService->atualizarSituacao(
            $edital,
            $request->validated()['situacao'],
            (string) $usuario->id
        );

        return new EditalResource($editalAtualizado);
    }
}
