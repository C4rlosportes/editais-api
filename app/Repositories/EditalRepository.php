<?php

namespace App\Repositories;

use App\Models\Edital;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Requisito;
use App\Models\Destino;

class EditalRepository
{
    /**
     * Lista os Editais cadastrados.
     */
    public function listar(): Collection
    {
        return Edital::query()
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Busca um edital pelo ID, incluindo seus requisitos e destinos.
     */
    public function buscarPorId(int $id): Edital
    {
        return Edital::query()
            ->with([
                'requisitos' => function ($query) {
                    $query->orderBy('ordem');
                },
                'destinos.opm',
            ])
            ->findOrFail($id);
    }

    /**
     * Busca um edital para uma alteração protegida por transação.
     */
    public function buscarParaAtualizacao(int $editalId): Edital
    {
        return Edital::query()
            ->lockForUpdate()
            ->findOrFail($editalId);
    }

    /**
     * Verifica se o edital possui requisitos e destinos.
     */
    public function possuiRequisitosEDestinos(int $editalId): bool
    {
        $edital = Edital::query()
            ->withCount(['requisitos', 'destinos'])
            ->findOrFail($editalId);

        return $edital->requisitos_count > 0
            && $edital->destinos_count > 0;
    }

    /**
     * Verifica se o edital possui inscrições associadas.
     */
    public function possuiInscricoes(int $editalId): bool
    {
        return Edital::query()
            ->whereKey($editalId)
            ->whereHas('inscricoes')
            ->exists();
    }

    /**
     * Persiste a nova situação do edital.
     */
    public function atualizarSituacao(
        Edital $edital,
        string $situacao
    ): Edital {
        $edital->situacao = $situacao;
        $edital->save();

        return $edital->refresh();
    }

    /**
     * Cria um edital.
     */
    public function criar(array $dados): Edital
    {
        return Edital::create($dados);
    }

    /**
     * Cria um requisito vinculado ao edital.
     */
    public function criarRequisito(
        Edital $edital,
        array $dados
    ): Requisito {
        return $edital->requisitos()->create($dados);
    }

    /**
     * Cria um destino vinculado ao edital.
     */
    public function criarDestino(
        Edital $edital,
        array $dados
    ): Destino {
        return $edital->destinos()->create($dados);
    }

    /**
     * Atualiza os dados principais do edital.
     */
    public function atualizarConteudo(
        Edital $edital,
        array $dados
    ): Edital {
        $edital->fill($dados);
        $edital->save();

        return $edital;
    }

    /**
     * Remove os requisitos atuais do edital.
     */
    public function removerRequisitos(Edital $edital): void
    {
        $edital->requisitos()->delete();
    }

    /**
     * Remove os destinos atuais do edital.
     */
    public function removerDestinos(Edital $edital): void
    {
        $edital->destinos()->delete();
    }
}
