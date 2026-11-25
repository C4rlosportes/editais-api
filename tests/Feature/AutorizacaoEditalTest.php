<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarContextoAutorizacaoEdital(): array
{
    $opm = Opm::create([
        'codigo' => 'OPM-AUT-EDITAL-' . str()->uuid(),
        'nome' => 'OPM de Autorização',
        'municipio' => 'Porto Alegre',
    ]);

    $edital = Edital::create([
        'codigo' => 'EDITAL-AUT-' . str()->uuid(),
        'titulo' => 'Edital de Autorização',
        'descricao' => 'Teste de permissões.',
        'max_opcoes' => 1,
        'situacao' => 'RASCUNHO',
    ]);

    Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de teste',
        'ordem' => 1,
    ]);

    $destino = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    return [$opm, $edital, $destino];
}

function dadosValidosCriarEditalAutorizacao(Opm $opm): array
{
    return [
        'codigo' => 'EDITAL-NOVO-' . str()->uuid(),
        'titulo' => 'Novo edital',
        'descricao' => 'Edital para teste de autorização.',
        'max_opcoes' => 1,
        'requisitos' => [
            ['descricao' => 'Requisito de teste'],
        ],
        'destinos' => [
            [
                'opm_id' => $opm->id,
                'vagas' => 2,
            ],
        ],
    ];
}

function dadosValidosEditarEditalAutorizacao(Opm $opm): array
{
    return [
        'titulo' => 'Edital atualizado',
        'descricao' => 'Conteúdo atualizado.',
        'max_opcoes' => 1,
        'requisitos' => [
            ['descricao' => 'Novo requisito'],
        ],
        'destinos' => [
            [
                'opm_id' => $opm->id,
                'vagas' => 3,
            ],
        ],
    ];
}

test('criar edital exige autenticação', function () {
    $opm = Opm::create([
        'codigo' => 'OPM-SEM-AUTH',
        'nome' => 'OPM Teste',
        'municipio' => 'Porto Alegre',
    ]);

    $this->postJson(
        '/api/editais',
        dadosValidosCriarEditalAutorizacao($opm)
    )->assertUnauthorized();

    $this->assertDatabaseCount('editais', 0);
});

test('militar não pode criar edital', function () {
    $opm = Opm::create([
        'codigo' => 'OPM-MIL-EDITAL',
        'nome' => 'OPM Teste',
        'municipio' => 'Porto Alegre',
    ]);

    $contextoMilitar = criarUsuarioMilitar($opm);

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->postJson(
            '/api/editais',
            dadosValidosCriarEditalAutorizacao($opm)
        )
        ->assertForbidden();

    $this->assertDatabaseCount('editais', 0);
});

test('editar conteúdo exige autenticação', function () {
    [$opm, $edital] = criarContextoAutorizacaoEdital();

    $this->putJson(
        "/api/editais/{$edital->id}/conteudo",
        dadosValidosEditarEditalAutorizacao($opm)
    )->assertUnauthorized();

    $this->assertDatabaseHas('editais', [
        'id' => $edital->id,
        'titulo' => 'Edital de Autorização',
    ]);
});

test('militar não pode editar conteúdo de edital', function () {
    [$opm, $edital] = criarContextoAutorizacaoEdital();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->putJson(
            "/api/editais/{$edital->id}/conteudo",
            dadosValidosEditarEditalAutorizacao($opm)
        )
        ->assertForbidden();

    $this->assertDatabaseHas('editais', [
        'id' => $edital->id,
        'titulo' => 'Edital de Autorização',
    ]);
});

test('alterar situação exige autenticação', function () {
    [, $edital] = criarContextoAutorizacaoEdital();

    $this->putJson(
        "/api/editais/{$edital->id}",
        ['situacao' => 'ABERTO']
    )->assertUnauthorized();
});

test('militar não pode alterar situação de edital', function () {
    [$opm, $edital] = criarContextoAutorizacaoEdital();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        )
        ->assertForbidden();

    $this->assertDatabaseHas('editais', [
        'id' => $edital->id,
        'situacao' => 'RASCUNHO',
    ]);
});
