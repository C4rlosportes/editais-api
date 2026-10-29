<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Militar;
use App\Models\Opm;


class MilitarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $opmBeta = Opm::where('codigo', 'OPM-DEMO-02')->firstOrFail();
        $opmDelta = Opm::where('codigo', 'OPM-DEMO-04')->firstOrFail();
        $opmZeta = Opm::where('codigo', 'OPM-DEMO-06')->firstOrFail();

        $militares = [
            [
                'usuario_keycloak' => 'demo.militar01',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-001',
                'nome' => 'Militar Demo 01',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmBeta->id,
                'municipio' => $opmBeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar02',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-002',
                'nome' => 'Militar Demo 02',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmDelta->id,
                'municipio' => $opmDelta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar03',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-003',
                'nome' => 'Militar Demo 03',
                'posto_graduacao' => '2º Sargento',
                'opm_origem_id' => $opmZeta->id,
                'municipio' => $opmZeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar04',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-004',
                'nome' => 'Militar Demo 04',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmBeta->id,
                'municipio' => $opmBeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar05',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-005',
                'nome' => 'Militar Demo 05',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmDelta->id,
                'municipio' => $opmDelta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar06',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-006',
                'nome' => 'Militar Demo 06',
                'posto_graduacao' => '2º Sargento',
                'opm_origem_id' => $opmZeta->id,
                'municipio' => $opmZeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar07',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-007',
                'nome' => 'Militar Demo 07',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmBeta->id,
                'municipio' => $opmBeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar08',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-008',
                'nome' => 'Militar Demo 08',
                'posto_graduacao' => 'Capitão',
                'opm_origem_id' => $opmDelta->id,
                'municipio' => $opmDelta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar09',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-009',
                'nome' => 'Militar Demo 09',
                'posto_graduacao' => 'Capitão',
                'opm_origem_id' => $opmZeta->id,
                'municipio' => $opmZeta->municipio,
            ],
            [
                'usuario_keycloak' => 'demo.militar10',
                'perfil' => 'MILITAR',
                'id_funcional' => 'MIL-DEMO-010',
                'nome' => 'Militar Demo 10',
                'posto_graduacao' => 'Soldado',
                'opm_origem_id' => $opmBeta->id,
                'municipio' => $opmBeta->municipio,
            ],
        ];

        foreach ($militares as $militar) {
            Militar::updateOrCreate(
                ['usuario_keycloak' => $militar['usuario_keycloak']],
                $militar
            );
        }
    }
}
