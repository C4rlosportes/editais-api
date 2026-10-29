<?php

namespace App\Repositories;

use App\Models\Inscricao;
use App\Models\Destino;
use App\Models\Conferencia;
use App\Models\Escolha;
use Illuminate\Database\Eloquent\Collection;

class InscricaoRepository
{
    public function existePorEditalEMilitar(int $editalId, int $militarId): bool  #Essa regra verifica se já existe uma inscrição para o militar em um determinado edital.
    {
        return Inscricao::where('edital_id', $editalId)
            ->where('militar_id', $militarId)
            ->exists();
    }

    public function quantidadeDestinosDoEdital(int $editalId, array $destinoIds): int #Verifica quantos dos destinos fornecidos pertencem ao edital especificado, garantindo que o militar não escolha destinos que não estão disponíveis para aquele edital.
    {
        return Destino::where('edital_id', $editalId)
            ->whereIn('id', $destinoIds)
            ->count();
    }

    public function listarPorEdital(int $editalId): Collection
    {
        return Inscricao::query()
            ->with([
                'militar.opmOrigem',
                'edital',
                'escolhas.destino.opm',
                'conferencias.requisito',
            ])
            ->where('edital_id', $editalId)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    public function criarInscricao(array $dados): Inscricao #Cria uma nova inscrição no banco de dados com os dados fornecidos.
    {
        return Inscricao::create($dados);
    }

    public function criarEscolha(
        int $inscricaoId,
        int $destinoId,
        int $ordem
    ): Escolha {
        return Escolha::create([
            'inscricao_id' => $inscricaoId,
            'destino_id' => $destinoId,
            'ordem' => $ordem,
        ]);
    }

    public function criarConferencia(
        int $inscricaoId,
        int $requisitoId
    ): Conferencia {
        return Conferencia::create([
            'inscricao_id' => $inscricaoId,
            'requisito_id' => $requisitoId,
            'resultado' => 'PENDENTE',
            'observacao' => null,
            'conferido_por' => null,
        ]);
    }

    public function buscarConferenciaDaInscricao(
        int $inscricaoId,
        int $conferenciaId
    ): Conferencia {
        return Conferencia::query()
            ->where('inscricao_id', $inscricaoId)
            ->whereKey($conferenciaId)
            ->firstOrFail();
    }

    public function atualizarConferencia(
        Conferencia $conferencia,
        array $dados
    ): Conferencia {
        $conferencia->update($dados);

        return $conferencia->refresh();
    }

    /**
     * Busca a inscrição e bloqueia sua linha durante a transação.
     */
    public function buscarParaAnalise(int $inscricaoId): Inscricao
    {
        return Inscricao::query()
            ->with(['edital', 'conferencias'])
            ->lockForUpdate()
            ->findOrFail($inscricaoId);
    }

    /**
     * Verifica se ainda existe alguma conferência pendente.
     */
    public function possuiConferenciaPendente(int $inscricaoId): bool
    {
        return Conferencia::query()
            ->where('inscricao_id', $inscricaoId)
            ->where('resultado', 'PENDENTE')
            ->exists();
    }

    /**
     * Verifica se alguma conferência foi marcada como não atendida.
     */
    public function possuiConferenciaNaoAtende(int $inscricaoId): bool
    {
        return Conferencia::query()
            ->where('inscricao_id', $inscricaoId)
            ->where('resultado', 'NAO_ATENDE')
            ->exists();
    }

    /**
     * Atualiza a situação e registra a conclusão, quando aplicável.
     */
    public function atualizarSituacaoAnalise(
        Inscricao $inscricao,
        string $situacao,
        bool $concluida = false
    ): Inscricao {
        $inscricao->situacao = $situacao;

        if ($concluida) {
            $inscricao->concluded_at = now();
        }

        $inscricao->version = ((int) $inscricao->version) + 1;

        $inscricao->save();

        return $inscricao->refresh();
    }
}
