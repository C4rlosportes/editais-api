<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'max_opcoes' => $this->max_opcoes,
            'situacao' => $this->situacao,

            'requisitos' => $this->whenLoaded(
                'requisitos',
                function ($requisitos) {
                    return $requisitos->map(function ($requisito) {
                        return [
                            'id' => $requisito->id,
                            'descricao' => $requisito->descricao,
                            'ordem' => $requisito->ordem,
                        ];
                    })->values();
                }
            ),

            'destinos' => $this->whenLoaded(
                'destinos',
                function ($destinos) {
                    return $destinos->map(function ($destino) {
                        return [
                            'id' => $destino->id,
                            'municipio' => $destino->opm?->municipio,
                            'vagas' => $destino->vagas,

                            'opm' => $destino->opm
                                ? [
                                    'id' => $destino->opm->id,
                                    'codigo' => $destino->opm->codigo,
                                    'nome' => $destino->opm->nome,
                                    'municipio' => $destino->opm->municipio,
                                ]
                                : null,
                        ];
                    })->values();
                }
            ),
        ];
    }
}
