<?php

namespace Database\Seeders;

use App\Models\Opm;
use Illuminate\Database\Seeder;

class OpmSeeder extends Seeder
{
    public function run(): void
    {
        Opm::updateOrCreate(
            ['codigo' => 'OPM-DEMO-02'],
            [
                'nome' => 'Batalhão de Teste Beta',
                'municipio' => 'Município Fictício Beta',
            ]
        );

        Opm::updateOrCreate(
            ['codigo' => 'OPM-DEMO-04'],
            [
                'nome' => 'Batalhão de Teste Delta',
                'municipio' => 'Município Fictício Delta',
            ]
        );

        Opm::updateOrCreate(
            ['codigo' => 'OPM-DEMO-06'],
            [
                'nome' => 'Batalhão de Teste Zeta',
                'municipio' => 'Município Fictício Zeta',
            ]
        );
    }
}
