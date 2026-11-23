<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Historico;

uses(RefreshDatabase::class);

beforeEach(function () {
    $dadosOpms = [
        [
            'codigo' => 'OPM-CRIAR-001',
            'nome' => 'OPM Porto Alegre',
            'municipio' => 'Porto Alegre',
        ],
        [
            'codigo' => 'OPM-CRIAR-002',
            'nome' => 'OPM Canoas',
            'municipio' => 'Canoas',
        ],
        [
            'codigo' => 'OPM-CRIAR-003',
            'nome' => 'OPM Gravataí',
            'municipio' => 'Gravataí',
        ],
        [
            'codigo' => 'OPM-CRIAR-004',
            'nome' => 'OPM Viamão',
            'municipio' => 'Viamão',
        ],
    ];

    $this->opms = [];

    foreach ($dadosOpms as $dadosOpm) {
        $this->opms[] = Opm::create($dadosOpm);
    }

    $this->dadosEdital = [
        'codigo' => 'EDITAL-CRIAR-001',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Edital criado para testar a API.',
        'max_opcoes' => 4,
        'requisitos' => [
            [
                'descricao' => 'Primeiro requisito',
            ],
            [
                'descricao' => 'Segundo requisito',
            ],
        ],
        'destinos' => array_map(
            fn($opm) => [
                'opm_id' => $opm->id,
                'vagas' => 2,
            ],
            $this->opms
        ),
    ];
    // Autenticar os testes com uma conta da SME.
    $this->usuarioSme = criarUsuarioSme();

    $this->actingAs($this->usuarioSme, 'sanctum');
});


test('a SME consegue criar um edital com requisitos e destinos', function () {
    $response = $this->postJson(
        '/api/editais',
        $this->dadosEdital
    );

    $response->assertCreated()
        ->assertJsonPath('data.codigo', 'EDITAL-CRIAR-001')
        ->assertJsonPath('data.titulo', 'Edital de Teste')
        ->assertJsonPath('data.max_opcoes', 4)
        ->assertJsonPath('data.situacao', 'RASCUNHO')
        ->assertJsonCount(2, 'data.requisitos')
        ->assertJsonCount(4, 'data.destinos');

    $edital = Edital::where(
        'codigo',
        'EDITAL-CRIAR-001'
    )->firstOrFail();

    $this->assertDatabaseHas('editais', [
        'id' => $edital->id,
        'situacao' => 'RASCUNHO',
        'max_opcoes' => 4,
    ]);

    $this->assertDatabaseCount('requisitos', 2);
    $this->assertDatabaseCount('destinos', 4);

    $this->assertSame(
        [
            'Primeiro requisito',
            'Segundo requisito',
        ],
        Requisito::where('edital_id', $edital->id)
            ->orderBy('ordem')
            ->pluck('descricao')
            ->all()
    );

    $this->assertSame(
        [1, 2],
        Requisito::where('edital_id', $edital->id)
            ->orderBy('ordem')
            ->pluck('ordem')
            ->all()
    );

    expect(
        Destino::where('edital_id', $edital->id)->count()
    )->toBe(4);

    $edital = \App\Models\Edital::query()
        ->latest('id')
        ->firstOrFail();

    $historico = Historico::query()
        ->where('edital_id', $edital->id)
        ->where('acao', 'EDITAL_CRIADO')
        ->first();

    expect($historico)->not->toBeNull();
    expect($historico->autor)
        ->toBe((string) $this->usuarioSme->id);
    expect($historico->dados_anteriores)->toBeNull();

    expect($historico->dados_posteriores['codigo'])
        ->toBe($edital->codigo);

    expect($historico->dados_posteriores['situacao'])
        ->toBe('RASCUNHO');

    expect($historico->dados_posteriores['requisitos'])
        ->not->toBeEmpty();

    expect($historico->dados_posteriores['destinos'])
        ->not->toBeEmpty();
});

test('a API rejeita max_opcoes maior que a quantidade de destinos', function () {
    $dados = $this->dadosEdital;
    $dados['max_opcoes'] = 5;

    $response = $this->postJson('/api/editais', $dados);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['max_opcoes']);

    $this->assertDatabaseCount('editais', 0);
    $this->assertDatabaseCount('requisitos', 0);
    $this->assertDatabaseCount('destinos', 0);
});

test('a API rejeita um código de edital duplicado', function () {
    Edital::create([
        'codigo' => 'EDITAL-CRIAR-001',
        'titulo' => 'Edital já existente',
        'descricao' => null,
        'max_opcoes' => 1,
        'situacao' => 'RASCUNHO',
    ]);

    $response = $this->postJson(
        '/api/editais',
        $this->dadosEdital
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['codigo']);

    $this->assertDatabaseCount('editais', 1);
});

test('a API rejeita uma OPM inexistente e não cria registros parciais', function () {
    $dados = $this->dadosEdital;
    $dados['destinos'][0]['opm_id'] = 999999;

    $response = $this->postJson('/api/editais', $dados);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'destinos.0.opm_id',
        ]);

    $this->assertDatabaseCount('editais', 0);
    $this->assertDatabaseCount('requisitos', 0);
    $this->assertDatabaseCount('destinos', 0);
});

test('a API exige pelo menos um requisito e um destino', function () {
    $dadosSemRequisitos = $this->dadosEdital;
    $dadosSemRequisitos['requisitos'] = [];

    $this->postJson('/api/editais', $dadosSemRequisitos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['requisitos']);

    $dadosSemDestinos = $this->dadosEdital;
    $dadosSemDestinos['destinos'] = [];
    $dadosSemDestinos['max_opcoes'] = 1;

    $this->postJson('/api/editais', $dadosSemDestinos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['destinos']);

    $this->assertDatabaseCount('editais', 0);
});

test('a API não permite definir a situação inicial no corpo da requisição', function () {
    $dados = $this->dadosEdital;
    $dados['situacao'] = 'ABERTO';

    $this->postJson('/api/editais', $dados)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['situacao']);

    $this->assertDatabaseCount('editais', 0);
});
