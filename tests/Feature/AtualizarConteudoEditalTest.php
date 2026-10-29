<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->opm1 = Opm::create([
        'codigo' => 'OPM-EDITAR-001',
        'nome' => 'OPM Alfa',
        'municipio' => 'Porto Alegre',
    ]);

    $this->opm2 = Opm::create([
        'codigo' => 'OPM-EDITAR-002',
        'nome' => 'OPM Beta',
        'municipio' => 'Canoas',
    ]);

    $this->edital = Edital::create([
        'codigo' => 'EDITAL-EDITAR-001',
        'titulo' => 'Edital Original',
        'descricao' => 'Descrição original.',
        'max_opcoes' => 1,
        'situacao' => 'RASCUNHO',
    ]);

    Requisito::create([
        'edital_id' => $this->edital->id,
        'descricao' => 'Requisito original.',
        'ordem' => 1,
    ]);

    Destino::create([
        'edital_id' => $this->edital->id,
        'opm_id' => $this->opm1->id,
        'vagas' => 2,
    ]);
});

test('a SME consegue editar o conteúdo de um edital em rascunho', function () {
    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'titulo' => 'Edital Atualizado',
            'descricao' => 'Descrição atualizada.',
            'max_opcoes' => 2,
            'requisitos' => [
                ['descricao' => 'Novo requisito 1.'],
                ['descricao' => 'Novo requisito 2.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm1->id,
                    'vagas' => 4,
                ],
                [
                    'opm_id' => $this->opm2->id,
                    'vagas' => 3,
                ],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.codigo', 'EDITAL-EDITAR-001')
        ->assertJsonPath('data.titulo', 'Edital Atualizado')
        ->assertJsonPath('data.descricao', 'Descrição atualizada.')
        ->assertJsonPath('data.max_opcoes', 2)
        ->assertJsonPath('data.situacao', 'RASCUNHO')
        ->assertJsonCount(2, 'data.requisitos')
        ->assertJsonCount(2, 'data.destinos')
        ->assertJsonPath('data.requisitos.0.ordem', 1)
        ->assertJsonPath('data.requisitos.0.descricao', 'Novo requisito 1.')
        ->assertJsonPath('data.requisitos.1.ordem', 2)
        ->assertJsonPath('data.requisitos.1.descricao', 'Novo requisito 2.')
        ->assertJsonPath('data.destinos.0.vagas', 4)
        ->assertJsonPath('data.destinos.1.vagas', 3);

    // O código original deve permanecer inalterado.
    $this->assertDatabaseHas('editais', [
        'id' => $this->edital->id,
        'codigo' => 'EDITAL-EDITAR-001',
        'titulo' => 'Edital Atualizado',
        'situacao' => 'RASCUNHO',
    ]);

    // Os requisitos e destinos foram substituídos.
    $this->assertDatabaseCount('requisitos', 2);
    $this->assertDatabaseCount('destinos', 2);

    $this->assertDatabaseHas('requisitos', [
        'edital_id' => $this->edital->id,
        'descricao' => 'Novo requisito 1.',
        'ordem' => 1,
    ]);

    $this->assertDatabaseHas('requisitos', [
        'edital_id' => $this->edital->id,
        'descricao' => 'Novo requisito 2.',
        'ordem' => 2,
    ]);

    $this->assertDatabaseHas('destinos', [
        'edital_id' => $this->edital->id,
        'opm_id' => $this->opm2->id,
        'vagas' => 3,
    ]);
});

test('a SME não consegue editar o conteúdo de um edital aberto', function () {
    $this->edital->update([
        'situacao' => 'ABERTO',
    ]);

    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'titulo' => 'Tentativa de Alteração',
            'descricao' => 'Esta alteração deve ser rejeitada.',
            'max_opcoes' => 1,
            'requisitos' => [
                ['descricao' => 'Requisito alterado.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm2->id,
                    'vagas' => 5,
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('edital');

    // Os dados principais devem permanecer inalterados.
    $this->assertDatabaseHas('editais', [
        'id' => $this->edital->id,
        'codigo' => 'EDITAL-EDITAR-001',
        'titulo' => 'Edital Original',
        'descricao' => 'Descrição original.',
        'max_opcoes' => 1,
        'situacao' => 'ABERTO',
    ]);

    // Os relacionamentos originais devem continuar existindo.
    $this->assertDatabaseCount('requisitos', 1);
    $this->assertDatabaseCount('destinos', 1);

    $this->assertDatabaseHas('requisitos', [
        'edital_id' => $this->edital->id,
        'descricao' => 'Requisito original.',
        'ordem' => 1,
    ]);

    $this->assertDatabaseHas('destinos', [
        'edital_id' => $this->edital->id,
        'opm_id' => $this->opm1->id,
        'vagas' => 2,
    ]);
});

test('a API rejeita limite de opções maior que a quantidade de destinos na edição', function () {
    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'titulo' => 'Edital Atualizado',
            'descricao' => 'Descrição atualizada.',
            'max_opcoes' => 2,
            'requisitos' => [
                ['descricao' => 'Requisito atualizado.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm1->id,
                    'vagas' => 3,
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('max_opcoes');

    // Uma requisição inválida não deve modificar o edital.
    $this->assertDatabaseHas('editais', [
        'id' => $this->edital->id,
        'codigo' => 'EDITAL-EDITAR-001',
        'titulo' => 'Edital Original',
        'max_opcoes' => 1,
        'situacao' => 'RASCUNHO',
    ]);

    $this->assertDatabaseCount('requisitos', 1);
    $this->assertDatabaseCount('destinos', 1);

    $this->assertDatabaseHas('requisitos', [
        'edital_id' => $this->edital->id,
        'descricao' => 'Requisito original.',
    ]);
});

test('a API não permite alterar o código do edital', function () {
    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'codigo' => 'NOVO-CODIGO-001',
            'titulo' => 'Tentativa de alterar o código',
            'descricao' => 'Teste de imutabilidade.',
            'max_opcoes' => 1,
            'requisitos' => [
                ['descricao' => 'Requisito de teste.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm1->id,
                    'vagas' => 2,
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('codigo');

    // Nenhum dado pode ser alterado quando o código é enviado.
    $this->assertDatabaseHas('editais', [
        'id' => $this->edital->id,
        'codigo' => 'EDITAL-EDITAR-001',
        'titulo' => 'Edital Original',
        'descricao' => 'Descrição original.',
        'max_opcoes' => 1,
        'situacao' => 'RASCUNHO',
    ]);

    $this->assertDatabaseCount('requisitos', 1);
    $this->assertDatabaseCount('destinos', 1);
});

test('a API não permite editar o conteúdo de um edital encerrado', function () {
    $this->edital->update([
        'situacao' => 'ENCERRADO',
    ]);

    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'titulo' => 'Tentativa de alteração',
            'descricao' => 'Esta alteração deve ser rejeitada.',
            'max_opcoes' => 1,
            'requisitos' => [
                ['descricao' => 'Requisito alterado.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm2->id,
                    'vagas' => 5,
                ],
            ],
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('edital');

    $this->assertDatabaseHas('editais', [
        'id' => $this->edital->id,
        'titulo' => 'Edital Original',
        'situacao' => 'ENCERRADO',
    ]);

    $this->assertDatabaseCount('requisitos', 1);
    $this->assertDatabaseCount('destinos', 1);
});

test('a API remove requisitos e destinos omitidos durante a edição', function () {
    Requisito::create([
        'edital_id' => $this->edital->id,
        'descricao' => 'Requisito a remover.',
        'ordem' => 2,
    ]);

    Destino::create([
        'edital_id' => $this->edital->id,
        'opm_id' => $this->opm2->id,
        'vagas' => 3,
    ]);

    $response = $this->putJson(
        "/api/editais/{$this->edital->id}/conteudo",
        [
            'titulo' => 'Edital Atualizado',
            'descricao' => 'Edição com menos itens.',
            'max_opcoes' => 1,
            'requisitos' => [
                ['descricao' => 'Requisito original.'],
            ],
            'destinos' => [
                [
                    'opm_id' => $this->opm1->id,
                    'vagas' => 2,
                ],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data.requisitos')
        ->assertJsonCount(1, 'data.destinos');

    $this->assertDatabaseCount('requisitos', 1);
    $this->assertDatabaseCount('destinos', 1);

    $this->assertDatabaseMissing('requisitos', [
        'edital_id' => $this->edital->id,
        'descricao' => 'Requisito a remover.',
    ]);

    $this->assertDatabaseMissing('destinos', [
        'edital_id' => $this->edital->id,
        'opm_id' => $this->opm2->id,
    ]);
});
