<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoricoResource extends JsonResource
{
    /**
     * Transforma o registro do histórico em JSON.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'edital_id' => $this->edital_id,
            'acao' => $this->acao,
            'autor' => $this->autor,
            'dados_anteriores' => $this->dados_anteriores,
            'dados_posteriores' => $this->dados_posteriores,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
