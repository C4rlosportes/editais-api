<?php

namespace App\Services;

use App\Models\Edital;
use App\Models\Militar;
use App\Repositories\InscricaoRepository;
use Illuminate\Validation\ValidationException;
use App\Models\Inscricao;
use App\Models\Conferencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use App\Repositories\HistoricoRepository;

class InscricaoService
{
    public function __construct(
        private InscricaoRepository $inscricaoRepository,
        private HistoricoRepository $historicoRepository
    ) {}

    public function listarPorEdital(int $editalId): Collection
    {
        return $this->inscricaoRepository->listarPorEdital($editalId);
    }

    public function atualizarConferencia(
        int $inscricaoId,
        int $conferenciaId,
        array $dados,
        string $autor
    ): Conferencia {
        return DB::transaction(function () use (
            $inscricaoId,
            $conferenciaId,
            $dados,
            $autor
        ) {
            $inscricao = $this->inscricaoRepository->buscarParaAnalise(
                $inscricaoId
            );

            $conferencia = $this->inscricaoRepository
                ->buscarConferenciaDaInscricao(
                    $inscricaoId,
                    $conferenciaId
                );

            if ($inscricao->situacao !== 'EM_ANALISE') {
                throw new ConflictHttpException(
                    'Só é possível atualizar conferências de inscrições em análise.'
                );
            }

            $dadosAnteriores = [
                'inscricao_id' => $inscricao->id,
                'conferencia_id' => $conferencia->id,
                'requisito_id' => $conferencia->requisito_id,
                'resultado' => $conferencia->resultado,
                'observacao' => $conferencia->observacao,
            ];

            $observacao = $dados['resultado'] === 'NAO_ATENDE'
                ? $dados['observacao']
                : null;

            $conferenciaAtualizada = $this->inscricaoRepository
                ->atualizarConferencia(
                    $conferencia,
                    [
                        'resultado' => $dados['resultado'],
                        'observacao' => $observacao,
                    ]
                );

            $dadosPosteriores = [
                'inscricao_id' => $inscricao->id,
                'conferencia_id' => $conferenciaAtualizada->id,
                'requisito_id' => $conferenciaAtualizada->requisito_id,
                'resultado' => $conferenciaAtualizada->resultado,
                'observacao' => $conferenciaAtualizada->observacao,
            ];

            $this->historicoRepository->registrar(
                $inscricao->edital_id,
                'CONFERENCIA_ATUALIZADA',
                $autor,
                $dadosAnteriores,
                $dadosPosteriores
            );

            return $conferenciaAtualizada;
        });
    }

    public function criar(
        array $dados,
        Edital $edital,
        Militar $militar,
        string $autor
    ): Inscricao {
        # Função para criar uma inscrição, recebendo os dados validados, o edital e o militar.

        if ($edital->situacao !== 'ABERTO') {
            throw ValidationException::withMessages([
                'edital' => 'O edital não está aberto para inscrições.', #Somente permite criar inscrição se o edital estiver aberto.
            ]);
        }

        if ($this->inscricaoRepository->existePorEditalEMilitar($edital->id, $militar->id)) { #Verifica se já existe uma inscrição para o militar no edital, evitando duplicidade de inscrições.
            throw ValidationException::withMessages([
                'inscricao' => 'O militar já está inscrito neste edital.',
            ]);
        }

        $destinoIds = $dados['destinos'];

        if (count($destinoIds) > $edital->max_opcoes) {
            throw ValidationException::withMessages([
                'destinos' => 'A quantidade de destinos escolhidos ultrapassa o limite do edital.',
            ]);
        }

        $quantidadeDestinosValidos =
            $this->inscricaoRepository->quantidadeDestinosDoEdital(
                $edital->id,
                $destinoIds
            );

        if ($quantidadeDestinosValidos !== count($destinoIds)) {
            throw ValidationException::withMessages([
                'destinos' => 'Um ou mais destinos não pertencem a este edital.',
            ]);
        }

        return DB::transaction(function () use ($dados, $edital, $militar, $destinoIds, $autor) { #Inicia uma transação de banco de dados para garantir que todas as operações sejam concluídas com sucesso ou revertidas em caso de falha.

            $inscricao = $this->inscricaoRepository->criarInscricao([
                'protocolo' => 'INS-' . strtoupper(Str::random(12)),
                'edital_id' => $edital->id,
                'militar_id' => $militar->id,
                'situacao' => 'SUBMETIDA',
                'aceite_condicoes' => $dados['aceite_condicoes'],
                'version' => 1,
                'submitted_at' => now(),
                'concluded_at' => null,
            ]);

            foreach ($destinoIds as $indice => $destinoId) {
                $this->inscricaoRepository->criarEscolha(
                    $inscricao->id,
                    $destinoId,
                    $indice + 1
                );
            }

            $requisitos = $edital->requisitos()->get();

            foreach ($requisitos as $requisito) {
                $this->inscricaoRepository->criarConferencia(
                    $inscricao->id,
                    $requisito->id
                );
            }

            // Registra os dados iniciais da inscrição no histórico do edital.
            $dadosPosteriores = [
                'inscricao_id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'militar_id' => $inscricao->militar_id,
                'situacao' => $inscricao->situacao,
                'aceite_condicoes' => (bool) $inscricao->aceite_condicoes,
                'version' => (int) $inscricao->version,
                'destinos' => array_values($destinoIds),
                'requisitos' => $requisitos->pluck('id')->all(),
            ];

            $this->historicoRepository->registrar(
                $edital->id,
                'INSCRICAO_CRIADA',
                $autor,
                null,
                $dadosPosteriores
            );

            return $inscricao;
        });
    }

    public function atualizarAnalise(
        int $inscricaoId,
        string $novaSituacao,
        string $autor
    ): Inscricao {
        return DB::transaction(function () use (
            $inscricaoId,
            $novaSituacao,
            $autor
        ) {
            $inscricao = $this->inscricaoRepository
                ->buscarParaAnalise($inscricaoId);

            $edital = $inscricao->edital;

            if ($novaSituacao === 'EM_ANALISE') {
                if ($inscricao->situacao !== 'SUBMETIDA') {
                    throw new ConflictHttpException(
                        'Somente inscrições submetidas podem entrar em análise.'
                    );
                }

                if ($edital->situacao !== 'EM_ANALISE') {
                    throw new ConflictHttpException(
                        'O edital precisa estar em EM_ANALISE para iniciar a análise da inscrição.'
                    );
                }

                $dadosAnteriores = [
                    'inscricao_id' => $inscricao->id,
                    'situacao' => $inscricao->situacao,
                    'version' => (int) $inscricao->version,
                ];

                $inscricaoAtualizada = $this->inscricaoRepository
                    ->atualizarSituacaoAnalise(
                        $inscricao,
                        'EM_ANALISE'
                    );

                $this->historicoRepository->registrar(
                    $edital->id,
                    'ANALISE_INICIADA',
                    $autor,
                    $dadosAnteriores,
                    [
                        'inscricao_id' => $inscricaoAtualizada->id,
                        'situacao' => $inscricaoAtualizada->situacao,
                        'version' => (int) $inscricaoAtualizada->version,
                    ]
                );

                return $inscricaoAtualizada;
            }

            if ($novaSituacao === 'CONCLUIDA') {
                if (
                    $inscricao->situacao !== 'EM_ANALISE'
                    || $edital->situacao !== 'EM_ANALISE'
                ) {
                    throw new ConflictHttpException(
                        'Apenas inscrições em análise podem ser concluídas.'
                    );
                }

                if (
                    $this->inscricaoRepository
                    ->possuiConferenciaPendente($inscricao->id)
                ) {
                    throw new ConflictHttpException(
                        'Todas as conferências precisam ser decididas antes da conclusão.'
                    );
                }

                $situacaoFinal = $this->inscricaoRepository
                    ->possuiConferenciaNaoAtende($inscricao->id)
                    ? 'INAPTA'
                    : 'APTA';

                $dadosAnteriores = [
                    'inscricao_id' => $inscricao->id,
                    'situacao' => $inscricao->situacao,
                    'version' => (int) $inscricao->version,
                ];

                $inscricaoAtualizada = $this->inscricaoRepository
                    ->atualizarSituacaoAnalise(
                        $inscricao,
                        $situacaoFinal,
                        true
                    );

                $this->historicoRepository->registrar(
                    $edital->id,
                    'ANALISE_CONCLUIDA',
                    $autor,
                    $dadosAnteriores,
                    [
                        'inscricao_id' => $inscricaoAtualizada->id,
                        'situacao' => $inscricaoAtualizada->situacao,
                        'version' => (int) $inscricaoAtualizada->version,
                    ]
                );

                return $inscricaoAtualizada;
            }

            throw ValidationException::withMessages([
                'situacao' => 'A situação informada não é válida para a análise.',
            ]);
        });
    }
}
