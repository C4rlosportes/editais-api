
<?php

use App\Models\Edital;
use App\Models\Historico;
use App\Models\Opm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a API lista o historico do edital do mais recente para o mais antigo', function () {
    $usuarioSme = criarUsuarioSme();

    $edital = Edital::create([
        'codigo' => 'EDITAL-HIST-001',
        'titulo' => 'Edital de Histórico',
        'descricao' => 'Edital utilizado no teste de histórico.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    $outroEdital = Edital::create([
        'codigo' => 'EDITAL-HIST-002',
        'titulo' => 'Outro Edital',
        'descricao' => 'Edital utilizado para testar o isolamento.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    $historicoAntigo = Historico::create([
        'edital_id' => $edital->id,
        'acao' => 'EDITAL_CRIADO',
        'autor' => null,
        'dados_anteriores' => null,
        'dados_posteriores' => [
            'titulo' => 'Edital de Histórico',
            'situacao' => 'RASCUNHO',
        ],
    ]);

    $historicoRecente = Historico::create([
        'edital_id' => $edital->id,
        'acao' => 'SITUACAO_ALTERADA',
        'autor' => null,
        'dados_anteriores' => [
            'situacao' => 'RASCUNHO',
        ],
        'dados_posteriores' => [
            'situacao' => 'ABERTO',
        ],
    ]);

    Historico::create([
        'edital_id' => $outroEdital->id,
        'acao' => 'EVENTO_OUTRO_EDITAL',
        'autor' => null,
        'dados_anteriores' => null,
        'dados_posteriores' => [
            'situacao' => 'RASCUNHO',
        ],
    ]);

    // Define datas diferentes para validar a ordenação.
    DB::table('historicos')
        ->where('id', $historicoAntigo->id)
        ->update([
            'created_at' => now()->subMinutes(10),
        ]);

    DB::table('historicos')
        ->where('id', $historicoRecente->id)
        ->update([
            'created_at' => now()->subMinute(),
        ]);

    $response = $this->actingAs($usuarioSme, 'sanctum')
        ->getJson("/api/editais/{$edital->id}/historico");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'edital_id',
                    'acao',
                    'autor',
                    'dados_anteriores',
                    'dados_posteriores',
                    'created_at',
                ],
            ],
        ])
        ->assertJsonPath('data.0.acao', 'SITUACAO_ALTERADA')
        ->assertJsonPath(
            'data.0.dados_anteriores.situacao',
            'RASCUNHO'
        )
        ->assertJsonPath(
            'data.0.dados_posteriores.situacao',
            'ABERTO'
        )
        ->assertJsonPath('data.1.acao', 'EDITAL_CRIADO')
        ->assertJsonPath(
            'data.1.dados_posteriores.titulo',
            'Edital de Histórico'
        );

    expect(
        collect($response->json('data'))
            ->pluck('edital_id')
            ->unique()
            ->values()
            ->all()
    )->toBe([$edital->id]);
});

test('a API retorna uma lista vazia quando o edital nao possui historico', function () {
    $usuarioSme = criarUsuarioSme();

    $edital = Edital::create([
        'codigo' => 'EDITAL-HIST-VAZIO',
        'titulo' => 'Edital sem Histórico',
        'descricao' => 'Edital sem eventos registrados.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    $this->actingAs($usuarioSme, 'sanctum')
        ->getJson("/api/editais/{$edital->id}/historico")
        ->assertOk()
        ->assertExactJson([
            'data' => [],
        ]);
});

test('a API retorna 404 ao consultar o historico de um edital inexistente', function () {
    $usuarioSme = criarUsuarioSme();

    $this->actingAs($usuarioSme, 'sanctum')
        ->getJson('/api/editais/999999/historico')
        ->assertNotFound();
});

test('usuario nao autenticado nao pode consultar o historico', function () {
    $edital = Edital::create([
        'codigo' => 'EDITAL-HIST-SEM-AUTH',
        'titulo' => 'Edital de teste',
        'descricao' => 'Teste de acesso sem autenticação.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    $this->getJson("/api/editais/{$edital->id}/historico")
        ->assertUnauthorized();
});

test('militar nao pode consultar o historico do edital', function () {
    $opm = Opm::create([
        'codigo' => 'OPM-HIST-' . str()->uuid(),
        'nome' => 'OPM de teste do histórico',
        'municipio' => 'Porto Alegre',
    ]);

    $contextoMilitar = criarUsuarioMilitar($opm);

    $edital = Edital::create([
        'codigo' => 'EDITAL-HIST-MILITAR',
        'titulo' => 'Edital de teste',
        'descricao' => 'Teste de acesso do militar.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->getJson("/api/editais/{$edital->id}/historico")
        ->assertForbidden();
});

test('a API retorna 401 em JSON sem autenticacao e sem cabecalho Accept', function () {
    $edital = Edital::create([
        'codigo' => 'EDITAL-HIST-SEM-ACCEPT',
        'titulo' => 'Edital sem autenticação',
        'descricao' => 'Teste de resposta sem cabeçalho Accept.',
        'max_opcoes' => 2,
        'situacao' => 'RASCUNHO',
    ]);

    // Usar get() em vez de getJson() para não enviar Accept: application/json.
    $response = $this->get(
        "/api/editais/{$edital->id}/historico"
    );

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});
