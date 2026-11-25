<?php

use App\Models\Conferencia;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarContextoAutorizacaoOperacoesInscricao(): array
{
    $identificador = (string) str()->uuid();

    $opm = Opm::create([
        'codigo' => 'OPM-AUT-OPS-' . $identificador,
        'nome' => 'OPM de Teste',
        'municipio' => 'Porto Alegre',
    ]);

    $contextoMilitar = criarUsuarioMilitar($opm);

    $edital = Edital::create([
        'codigo' => 'EDITAL-AUT-OPS-' . $identificador,
        'titulo' => 'Edital de Autorização',
        'descricao' => 'Teste de permissões administrativas.',
        'max_opcoes' => 1,
        'situacao' => 'EM_ANALISE',
    ]);

    $requisito = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de teste',
        'ordem' => 1,
    ]);

    $inscricao = Inscricao::create([
        'protocolo' => 'INS-AUT-OPS-' . $identificador,
        'edital_id' => $edital->id,
        'militar_id' => $contextoMilitar['militar']->id,
        'situacao' => 'EM_ANALISE',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => now(),
        'concluded_at' => null,
    ]);

    $conferencia = Conferencia::create([
        'inscricao_id' => $inscricao->id,
        'requisito_id' => $requisito->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
        'conferido_por' => null,
    ]);

    return [
        'usuarioMilitar' => $contextoMilitar['user'],
        'inscricao' => $inscricao,
        'conferencia' => $conferencia,
    ];
}

test('usuário não autenticado não pode atualizar conferência', function () {
    $contexto = criarContextoAutorizacaoOperacoesInscricao();

    $this->patchJson(
        "/api/inscricoes/{$contexto['inscricao']->id}/conferencias/{$contexto['conferencia']->id}",
        [
            'resultado' => 'ATENDE',
        ]
    )->assertUnauthorized();
});

test('militar não pode atualizar conferência', function () {
    $contexto = criarContextoAutorizacaoOperacoesInscricao();

    $this->actingAs($contexto['usuarioMilitar'], 'sanctum')
        ->patchJson(
            "/api/inscricoes/{$contexto['inscricao']->id}/conferencias/{$contexto['conferencia']->id}",
            [
                'resultado' => 'ATENDE',
            ]
        )
        ->assertForbidden();

    $this->assertDatabaseHas('conferencias', [
        'id' => $contexto['conferencia']->id,
        'resultado' => 'PENDENTE',
    ]);
});

test('usuário não autenticado não pode atualizar análise', function () {
    $contexto = criarContextoAutorizacaoOperacoesInscricao();

    $this->putJson(
        "/api/inscricoes/{$contexto['inscricao']->id}/analise",
        [
            'situacao' => 'CONCLUIDA',
        ]
    )->assertUnauthorized();
});

test('militar não pode atualizar análise', function () {
    $contexto = criarContextoAutorizacaoOperacoesInscricao();

    $this->actingAs($contexto['usuarioMilitar'], 'sanctum')
        ->putJson(
            "/api/inscricoes/{$contexto['inscricao']->id}/analise",
            [
                'situacao' => 'CONCLUIDA',
            ]
        )
        ->assertForbidden();

    $this->assertDatabaseHas('inscricoes', [
        'id' => $contexto['inscricao']->id,
        'situacao' => 'EM_ANALISE',
    ]);
});
