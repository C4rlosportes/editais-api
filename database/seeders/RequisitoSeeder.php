<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\Requisito;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequisitoSeeder extends Seeder
{
    public function run(): void
    {
        $edital = Edital::where('codigo', 'EDITAL-DEMO-002')->firstOrFail();

        Requisito::updateOrCreate(
            [
                'edital_id' => $edital->id,
                'ordem' => 1,
            ],
            [
                'descricao' => 'Vínculo ativo na base de teste',
            ]
        );

        Requisito::updateOrCreate(
            [
                'edital_id' => $edital->id,
                'ordem' => 2,
            ],
            [
                'descricao' => 'Cadastro conferido',
            ]
        );

        Requisito::updateOrCreate(
            [
                'edital_id' => $edital->id,
                'ordem' => 3,
            ],
            [
                'descricao' => 'Declaração de interesse conferida',
            ]
        );
    }
}
