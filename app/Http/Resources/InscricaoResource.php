<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoResource extends JsonResource
{
    /**
     * Transform the resource into um array para o frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'situacao' => $this->situacao,
            'aceite_condicoes' => $this->aceite_condicoes,

            'militar' => $this->whenLoaded('militar', function () { #O método whenLoaded verifica se a relação 'militar' foi carregada antes de tentar acessá-la, evitando erros caso a relação não esteja disponível.
                $militar = $this->militar;
                $opm = $militar->opmOrigem;

                return [
                    'nome' => $militar->nome,
                    'posto_graduacao' => $militar->posto_graduacao,
                    'id_funcional' => $militar->id_funcional,
                    'opm' => $opm ? [
                        'nome' => $opm->nome,
                        'municipio' => $opm->municipio,
                    ] : null,
                ];
            }),

            'edital' => $this->whenLoaded('edital', function () {
                $edital = $this->edital;

                return [
                    'codigo' => $edital->codigo,
                    'titulo' => $edital->titulo,
                ];
            }),

            'escolhas' => $this->whenLoaded('escolhas', function () {
                return $this->escolhas
                    ->sortBy('ordem')
                    ->values()
                    ->map(function ($escolha) {
                        return [
                            'ordem' => $escolha->ordem,
                            'destino' => [
                                'opm' => $escolha->destino->opm->nome,
                                'municipio' => $escolha->destino->opm?->municipio,
                            ],
                        ];
                    });
            }),

            'requisitos' => $this->whenLoaded('conferencias', function () {
                return $this->conferencias
                    ->sortBy(
                        fn($conferencia) => $conferencia->requisito->ordem
                    )
                    ->values()
                    ->map(function ($conferencia) {
                        return [
                            'conferencia_id' => $conferencia->id,
                            'ordem' => $conferencia->requisito->ordem,
                            'descricao' => $conferencia->requisito->descricao,
                            'resultado' => $conferencia->resultado,
                            'observacao' => $conferencia->observacao,
                        ];
                    });
            }),
        ];
    }
}
