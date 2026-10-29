<?php

namespace Database\Seeders;

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use Illuminate\Database\Seeder;

class DestinoSeeder extends Seeder
{
    public function run(): void
    {
        $editalAberto = Edital::where('codigo', 'EDITAL-DEMO-002')->firstOrFail();
        $editalRascunho = Edital::where('codigo', 'EDITAL-DEMO-001')->firstOrFail();

        $opmBeta = Opm::where('codigo', 'OPM-DEMO-02')->firstOrFail();
        $opmDelta = Opm::where('codigo', 'OPM-DEMO-04')->firstOrFail();
        $opmZeta = Opm::where('codigo', 'OPM-DEMO-06')->firstOrFail();

        Destino::updateOrCreate(
            [
                'edital_id' => $editalAberto->id,
                'opm_id' => $opmBeta->id,
            ],
            [
                'municipio' => $opmBeta->municipio,
                'vagas' => 5,
            ]
        );

        Destino::updateOrCreate(
            [
                'edital_id' => $editalAberto->id,
                'opm_id' => $opmDelta->id,
            ],
            [
                'municipio' => $opmDelta->municipio,
                'vagas' => 3,
            ]
        );

        Destino::updateOrCreate(
            [
                'edital_id' => $editalAberto->id,
                'opm_id' => $opmZeta->id,
            ],
            [
                'municipio' => $opmZeta->municipio,
                'vagas' => 2,
            ]
        );

        Destino::updateOrCreate(
            [
                'edital_id' => $editalRascunho->id,
                'opm_id' => $opmBeta->id,
            ],
            [
                'municipio' => $opmBeta->municipio,
                'vagas' => 4,
            ]
        );
    }
}
