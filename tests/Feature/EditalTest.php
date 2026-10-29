<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a API lista os editais cadastrados', function () {
    Edital::create([
        'codigo' => 'EDITAL-TESTE-001',
        'titulo' => 'Edital de Teste 001',
        'descricao' => 'Primeiro edital de teste.',
        'max_opcoes' => 3,
        'situacao' => 'RASCUNHO',
    ]);

    Edital::create([
        'codigo' => 'EDITAL-TESTE-002',
        'titulo' => 'Edital de Teste 002',
        'descricao' => 'Segundo edital de teste.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    $response = $this->getJson('/api/editais');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'codigo',
                    'titulo',
                    'descricao',
                    'max_opcoes',
                    'situacao',
                ],
            ],
        ])
        ->assertJsonPath('data.0.codigo', 'EDITAL-TESTE-001')
        ->assertJsonPath('data.1.codigo', 'EDITAL-TESTE-002');
});

test('a API retorna um edital com requisitos destinos e OPM', function () {
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-001',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-DETALHES',
        'titulo' => 'Edital de Teste Detalhado',
        'descricao' => 'Teste dos relacionamentos.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // Criar primeiro o requisito de ordem 2 para verificar
    // se o Repository retorna os requisitos na ordem correta.
    Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Segundo requisito',
        'ordem' => 2,
    ]);

    Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Primeiro requisito',
        'ordem' => 1,
    ]);

    Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 5,
    ]);

    $response = $this->getJson("/api/editais/{$edital->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'codigo',
                'titulo',
                'descricao',
                'max_opcoes',
                'situacao',
                'requisitos' => [
                    [
                        'id',
                        'descricao',
                        'ordem',
                    ],
                ],
                'destinos' => [
                    [
                        'id',
                        'municipio',
                        'vagas',
                        'opm' => [
                            'id',
                            'codigo',
                            'nome',
                            'municipio',
                        ],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.codigo', 'EDITAL-TESTE-DETALHES')
        ->assertJsonPath('data.situacao', 'ABERTO')
        ->assertJsonPath('data.requisitos.0.ordem', 1)
        ->assertJsonPath('data.requisitos.0.descricao', 'Primeiro requisito')
        ->assertJsonPath('data.requisitos.1.ordem', 2)
        ->assertJsonPath('data.destinos.0.vagas', 5)
        ->assertJsonPath('data.destinos.0.opm.codigo', 'OPM-TESTE-001');
});

test('a API retorna 404 ao consultar um edital inexistente', function () {
    $response = $this->getJson('/api/editais/999999');

    $response->assertNotFound();
});
