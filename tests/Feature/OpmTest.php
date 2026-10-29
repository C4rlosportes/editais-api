<?php

use App\Models\Opm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a API lista as OPMs cadastradas em ordem alfabética', function () {
    Opm::create([
        'codigo' => 'OPM-TESTE-003',
        'nome' => 'Zeta',
        'municipio' => 'Porto Alegre',
    ]);

    Opm::create([
        'codigo' => 'OPM-TESTE-001',
        'nome' => 'Alfa',
        'municipio' => 'Canoas',
    ]);

    Opm::create([
        'codigo' => 'OPM-TESTE-002',
        'nome' => 'Beta',
        'municipio' => 'Gravataí',
    ]);

    $response = $this->getJson('/api/opms');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'codigo',
                    'nome',
                    'municipio',
                ],
            ],
        ])
        ->assertJsonPath('data.0.nome', 'Alfa')
        ->assertJsonPath('data.1.nome', 'Beta')
        ->assertJsonPath('data.2.nome', 'Zeta');
});

test('a API retorna uma lista vazia quando não existem OPMs', function () {
    $response = $this->getJson('/api/opms');

    $response->assertOk()
        ->assertExactJson([
            'data' => [],
        ]);
});
