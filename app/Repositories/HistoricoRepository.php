<?php

namespace App\Repositories;

use App\Models\Historico;
use Illuminate\Database\Eloquent\Collection;

class HistoricoRepository
{
    /**
     * Registra um evento relacionado a um edital.
     */
    public function registrar(
        int $editalId,
        string $acao,
        ?string $autor = null,
        ?array $dadosAnteriores = null,
        ?array $dadosPosteriores = null
    ): Historico {
        return Historico::create([
            'edital_id' => $editalId,
            'acao' => $acao,
            'autor' => $autor,
            'dados_anteriores' => $dadosAnteriores,
            'dados_posteriores' => $dadosPosteriores,
        ]);
    }

    /**
     * Lista o histórico de um edital, do mais recente
     * para o mais antigo.
     */
    public function listarPorEdital(int $editalId): Collection
    {
        return Historico::query()
            ->where('edital_id', $editalId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
