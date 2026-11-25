<?php

namespace App\Services;

use App\Models\Edital;
use App\Repositories\EditalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Repositories\HistoricoRepository;

class EditalService
{
    /**
     * Cria um edital com seus requisitos e destinos.
     */
    public function criar(array $dados, string $autor): Edital
    {
        return DB::transaction(function () use ($dados, $autor) {
            $requisitos = $dados['requisitos'];
            $destinos = $dados['destinos'];

            unset($dados['requisitos'], $dados['destinos']);

            // Todo edital novo começa em RASCUNHO.
            $dados['situacao'] = 'RASCUNHO';

            $edital = $this->editalRepository->criar($dados);

            // Cria os requisitos na ordem em que foram enviados.
            foreach ($requisitos as $indice => $requisito) {
                $this->editalRepository->criarRequisito(
                    $edital,
                    [
                        'descricao' => $requisito['descricao'],
                        'ordem' => $indice + 1,
                    ]
                );
            }

            // Cria os destinos vinculados ao edital.
            foreach ($destinos as $destino) {
                $this->editalRepository->criarDestino(
                    $edital,
                    $destino
                );
            }

            $editalCriado = $this->editalRepository->buscarPorId($edital->id);

            $dadosPosteriores = $this->dadosDoEditalParaHistorico(
                $editalCriado
            );

            $this->historicoRepository->registrar(
                $editalCriado->id,
                'EDITAL_CRIADO',
                $autor,
                null,
                $dadosPosteriores
            );

            return $editalCriado;
        });
    }

    /**
     * Atualiza o conteúdo de um edital que ainda está em RASCUNHO.
     */
    public function atualizarConteudo(
        Edital $edital,
        array $dados,
        string $autor
    ): Edital {
        return DB::transaction(function () use ($edital, $dados, $autor) {
            $editalAtual = $this->editalRepository
                ->buscarParaAtualizacao($edital->id);

            if ($editalAtual->situacao !== 'RASCUNHO') {
                throw ValidationException::withMessages([
                    'edital' => 'Somente editais em RASCUNHO podem ter seu conteúdo alterado.',
                ]);
            }

            if ($this->editalRepository->possuiInscricoes($editalAtual->id)) {
                throw ValidationException::withMessages([
                    'edital' => 'Não é possível alterar o conteúdo de um edital que possui inscrições associadas.',
                ]);
            }

            // Captura os dados anteriores antes de qualquer alteração.
            $dadosAnteriores = $this->dadosDoEditalParaHistorico(
                $this->editalRepository->buscarPorId($editalAtual->id)
            );

            $requisitos = $dados['requisitos'];
            $destinos = $dados['destinos'];

            unset($dados['requisitos'], $dados['destinos']);

            // Atualiza os dados principais, preservando o código original.
            $this->editalRepository->atualizarConteudo(
                $editalAtual,
                $dados
            );

            // Substitui os requisitos e destinos existentes.
            $this->editalRepository->removerRequisitos($editalAtual);
            $this->editalRepository->removerDestinos($editalAtual);

            foreach ($requisitos as $indice => $requisito) {
                $this->editalRepository->criarRequisito(
                    $editalAtual,
                    [
                        'descricao' => $requisito['descricao'],
                        'ordem' => $indice + 1,
                    ]
                );
            }

            foreach ($destinos as $destino) {
                $this->editalRepository->criarDestino(
                    $editalAtual,
                    $destino
                );
            }

            // Busca o estado atualizado, incluindo os relacionamentos.
            $editalAtualizado = $this->editalRepository->buscarPorId(
                $editalAtual->id
            );

            $dadosPosteriores = $this->dadosDoEditalParaHistorico(
                $editalAtualizado
            );

            // Registra o histórico dentro da mesma transação.
            $this->historicoRepository->registrar(
                $editalAtualizado->id,
                'CONTEUDO_EDITAL_ATUALIZADO',
                $autor,
                $dadosAnteriores,
                $dadosPosteriores
            );

            return $editalAtualizado;
        });
    }

    public function __construct(
        private EditalRepository $editalRepository,
        private HistoricoRepository $historicoRepository
    ) {}

    /**
     * Lista os editais cadastrados.
     */
    public function listar(): Collection
    {
        return $this->editalRepository->listar();
    }

    /**
     * Busca um edital específico pelo ID.
     */
    public function buscarPorId(int $id): Edital
    {
        return $this->editalRepository->buscarPorId($id);
    }

    public function atualizarSituacao(
        Edital $edital,
        string $novaSituacao,
        string $autor
    ): Edital {
        return DB::transaction(function () use ($edital, $novaSituacao, $autor) {
            $editalAtual = $this->editalRepository
                ->buscarParaAtualizacao($edital->id);

            $proximaSituacao = match ($editalAtual->situacao) {
                'RASCUNHO' => 'ABERTO',
                'ABERTO' => 'ENCERRADO',
                'ENCERRADO' => 'EM_ANALISE',
                default => null,
            };

            if ($proximaSituacao !== $novaSituacao) {
                throw ValidationException::withMessages([
                    'situacao' => 'A transição solicitada não é permitida.',
                ]);
            }

            if (
                $novaSituacao === 'ABERTO'
                && ! $this->editalRepository
                    ->possuiRequisitosEDestinos($editalAtual->id)
            ) {
                throw ValidationException::withMessages([
                    'situacao' => 'Para abrir o edital, cadastre pelo menos um requisito e um destino.',
                ]);
            }

            $situacaoAnterior = $editalAtual->situacao;

            $editalAtualizado = $this->editalRepository->atualizarSituacao(
                $editalAtual,
                $novaSituacao
            );

            $this->historicoRepository->registrar(
                $editalAtualizado->id,
                'SITUACAO_ALTERADA',
                $autor,
                [
                    'situacao' => $situacaoAnterior,
                ],
                [
                    'situacao' => $editalAtualizado->situacao,
                ]
            );

            return $editalAtualizado;
        });
    }

    /**
     * Prepara os dados do edital para registro no histórico.
     */
    private function dadosDoEditalParaHistorico(Edital $edital): array
    {
        return [
            'codigo' => $edital->codigo,
            'titulo' => $edital->titulo,
            'descricao' => $edital->descricao,
            'max_opcoes' => $edital->max_opcoes,
            'situacao' => $edital->situacao,

            'requisitos' => $edital->requisitos
                ->sortBy('ordem')
                ->values()
                ->map(function ($requisito) {
                    return [
                        'id' => $requisito->id,
                        'descricao' => $requisito->descricao,
                        'ordem' => $requisito->ordem,
                    ];
                })
                ->all(),

            'destinos' => $edital->destinos
                ->values()
                ->map(function ($destino) {
                    return [
                        'id' => $destino->id,
                        'opm_id' => $destino->opm_id,
                        'opm' => $destino->opm ? [
                            'codigo' => $destino->opm->codigo,
                            'nome' => $destino->opm->nome,
                            'municipio' => $destino->opm->municipio,
                        ] : null,
                        'vagas' => $destino->vagas,
                    ];
                })
                ->all(),
        ];
    }
}
