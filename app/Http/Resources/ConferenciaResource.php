<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConferenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inscricao_id' => $this->inscricao_id,
            'requisito_id' => $this->requisito_id,

            'requisito' => $this->whenLoaded('requisito', function () {
                return [
                    'descricao' => $this->requisito->descricao,
                    'ordem' => $this->requisito->ordem,
                ];
            }),

            'resultado' => $this->resultado,
            'observacao' => $this->observacao,
            'updated_at' => $this->updated_at,
        ];
    }
}
