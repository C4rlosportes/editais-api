<?php

namespace Database\Seeders;

use App\Models\Edital;
use Illuminate\Database\Seeder;

class EditalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Edital::updateOrCreate(
            ['codigo' => 'EDITAL-DEMO-001'],
            [
                'titulo' => 'Demonstração de edital rascunho',
                'descricao' => 'Edital fictício aberto para testes de criação e edição.',
                'max_opcoes' => 3,
                'situacao' => 'RASCUNHO',
            ]
        );

        Edital::updateOrCreate(
            ['codigo' => 'EDITAL-DEMO-002'],
            [
                'titulo' => 'Demonstração de edital aberto',
                'descricao' => 'Edital fictício aberto para testes de consulta e inscrição.',
                'max_opcoes' => 3,
                'situacao' => 'ABERTO',
            ]
        );

        Edital::updateOrCreate(
            ['codigo' => 'EDITAL-DEMO-003'],
            [
                'titulo' => 'Demonstração de edital encerrado',
                'descricao' => 'Edital fictício encerrado para testes de análise e conclusão.',
                'max_opcoes' => 3,
                'situacao' => 'ENCERRADO',
            ]
        );

        Edital::updateOrCreate(
            ['codigo' => 'EDITAL-DEMO-004'],
            [
                'titulo' => 'Demonstração de edital em análise',
                'descricao' => 'Edital fictício para representar a etapa de análise pela SME.',
                'max_opcoes' => 3,
                'situacao' => 'EM_ANALISE',
            ]
        );
    }
}
