<?php

use App\Models\Conferencia;
use App\Models\Destino;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\Militar;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Historico;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-CONF-001',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // Criar um militar
    $militar = Militar::create([
        'usuario_keycloak' => 'usuario-conf-001',
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-CONF-001',
        'nome' => 'Militar de Teste',
        'posto_graduacao' => 'Soldado',
        'opm_origem_id' => $opm->id,
        'municipio' => 'Município de Teste',
    ]);

    // Criar um edital
    $edital = Edital::create([
        'codigo' => 'EDITAL-CONF-001',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Teste de atualização de conferência.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // Criar um requisito
    $requisito = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de teste',
        'ordem' => 1,
    ]);

    // Criar uma inscrição
    $inscricao = Inscricao::create([
        'protocolo' => 'INS-CONF-001',
        'edital_id' => $edital->id,
        'militar_id' => $militar->id,
        'situacao' => 'SUBMETIDA',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => now(),
        'concluded_at' => null,
    ]);

    // Criar uma conferência inicialmente pendente
    $conferencia = Conferencia::create([
        'inscricao_id' => $inscricao->id,
        'requisito_id' => $requisito->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
        'conferido_por' => null,
    ]);

    $this->inscricao = $inscricao;
    $this->conferencia = $conferencia;

    // Autenticar os testes como usuário da SME.
    $usuarioSme = criarUsuarioSme();

    $this->actingAs($usuarioSme, 'sanctum');
});

test('a SME pode marcar uma conferência como ATENDE', function () {
    // Preparar o cenário de uma análise em andamento.
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'ATENDE',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.resultado', 'ATENDE')
        ->assertJsonPath('data.observacao', null);

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'ATENDE',
        'observacao' => null,
    ]);
});

test('a SME pode marcar uma conferência como NAO_ATENDE com observação', function () {
    // Preparar o cenário de uma análise em andamento.
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'NAO_ATENDE',
            'observacao' => 'O requisito não foi comprovado.',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.resultado', 'NAO_ATENDE')
        ->assertJsonPath(
            'data.observacao',
            'O requisito não foi comprovado.'
        );

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'NAO_ATENDE',
        'observacao' => 'O requisito não foi comprovado.',
    ]);
});


test('a API rejeita NAO_ATENDE sem observação', function () {
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'NAO_ATENDE',
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['observacao']);

    // A conferência deve continuar pendente.
    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a API não permite atualizar uma conferência de outra inscrição', function () {
    // Criar outra inscrição para o mesmo militar e edital não seria válido
    // pelas regras de negócio, então criamos outro militar e edital.
    $opm = Opm::create([
        'codigo' => 'OPM-CONF-002',
        'nome' => 'Segunda OPM de Teste',
        'municipio' => 'Outro Município',
    ]);

    $militar = Militar::create([
        'usuario_keycloak' => 'usuario-conf-002',
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-CONF-002',
        'nome' => 'Segundo Militar de Teste',
        'posto_graduacao' => 'Cabo',
        'opm_origem_id' => $opm->id,
        'municipio' => 'Outro Município',
    ]);

    $edital = Edital::create([
        'codigo' => 'EDITAL-CONF-002',
        'titulo' => 'Segundo Edital',
        'descricao' => 'Teste de vínculo da conferência.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    $requisito = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Outro requisito',
        'ordem' => 1,
    ]);

    $outraInscricao = Inscricao::create([
        'protocolo' => 'INS-CONF-002',
        'edital_id' => $edital->id,
        'militar_id' => $militar->id,
        'situacao' => 'SUBMETIDA',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => now(),
        'concluded_at' => null,
    ]);

    $outraConferencia = Conferencia::create([
        'inscricao_id' => $outraInscricao->id,
        'requisito_id' => $requisito->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
        'conferido_por' => null,
    ]);

    // Informar a inscrição original, mas a conferência da outra inscrição.
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$outraConferencia->id}",
        [
            'resultado' => 'ATENDE',
        ]
    );

    $response->assertNotFound();

    // A conferência não deve ter sido alterada.
    $this->assertDatabaseHas('conferencias', [
        'id' => $outraConferencia->id,
        'resultado' => 'PENDENTE',
    ]);
});

test('a API rejeita um resultado de conferência inválido', function () {
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'RESULTADO_INVALIDO',
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['resultado']);

    // O resultado deve continuar pendente.
    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a API rejeita uma observação com mais de mil caracteres', function () {
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'NAO_ATENDE',
            'observacao' => str_repeat('a', 1001),
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['observacao']);

    // A tentativa inválida não deve modificar a conferência.
    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a API não permite atualizar conferência de inscrição submetida', function () {
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'ATENDE',
        ]
    );

    $response->assertStatus(409);

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a API não permite atualizar conferência de inscrição apta', function () {
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'APTA',
    ]);

    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'ATENDE',
        ]
    );

    $response->assertStatus(409);
    $this->assertDatabaseCount('historicos', 0);

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a API não permite atualizar conferência de inscrição inapta', function () {
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'INAPTA',
    ]);

    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'ATENDE',
        ]
    );

    $response->assertStatus(409);

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('a SME pode voltar uma conferência para PENDENTE durante a análise', function () {
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'EM_ANALISE',
    ]);

    // Simular uma conferência que já havia sido respondida.
    $this->conferencia->update([
        'resultado' => 'ATENDE',
        'observacao' => null,
    ]);

    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'PENDENTE',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.resultado', 'PENDENTE')
        ->assertJsonPath('data.observacao', null);

    $this->assertDatabaseHas('conferencias', [
        'id' => $this->conferencia->id,
        'resultado' => 'PENDENTE',
        'observacao' => null,
    ]);
});

test('registra historico ao atualizar uma conferencia', function () {
    // Preparar o edital e a inscrição para análise.
    Edital::findOrFail($this->inscricao->edital_id)->update([
        'situacao' => 'EM_ANALISE',
    ]);

    $this->inscricao->update([
        'situacao' => 'EM_ANALISE',
    ]);

    // Atualizar a conferência que inicialmente está pendente.
    $response = $this->patchJson(
        "/api/inscricoes/{$this->inscricao->id}/conferencias/{$this->conferencia->id}",
        [
            'resultado' => 'NAO_ATENDE',
            'observacao' => 'O requisito não foi comprovado.',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.resultado', 'NAO_ATENDE')
        ->assertJsonPath(
            'data.observacao',
            'O requisito não foi comprovado.'
        );

    // Confirmar que foi criado exatamente um registro histórico.
    $this->assertDatabaseCount('historicos', 1);

    $historico = Historico::query()->firstOrFail();

    $this->assertEquals(
        $this->inscricao->edital_id,
        $historico->edital_id
    );

    $this->assertSame(
        'CONFERENCIA_ATUALIZADA',
        $historico->acao
    );

    // O autor permanece nulo enquanto não há autenticação.
    // O histórico identifica o usuário SME autenticado.
    $this->assertSame(
        (string) auth('sanctum')->id(),
        $historico->autor
    );

    // Verificar os dados anteriores à atualização.
    $this->assertSame(
        [
            'inscricao_id' => $this->inscricao->id,
            'conferencia_id' => $this->conferencia->id,
            'requisito_id' => $this->conferencia->requisito_id,
            'resultado' => 'PENDENTE',
            'observacao' => null,
        ],
        $historico->dados_anteriores
    );

    // Verificar os dados posteriores à atualização.
    $this->assertSame(
        [
            'inscricao_id' => $this->inscricao->id,
            'conferencia_id' => $this->conferencia->id,
            'requisito_id' => $this->conferencia->requisito_id,
            'resultado' => 'NAO_ATENDE',
            'observacao' => 'O requisito não foi comprovado.',
        ],
        $historico->dados_posteriores
    );
});
