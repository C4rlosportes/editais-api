<?php

use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\Militar;
use App\Models\Opm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a API lista somente as inscrições do edital solicitado', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-LISTA-001',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar dois militares
    $militar1 = Militar::create([
        'usuario_keycloak' => 'usuario-lista-001',
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-LISTA-001',
        'nome' => 'Militar de Teste 1',
        'posto_graduacao' => 'Soldado',
        'opm_origem_id' => $opm->id,
        'municipio' => 'Município de Teste',
    ]);

    $militar2 = Militar::create([
        'usuario_keycloak' => 'usuario-lista-002',
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-LISTA-002',
        'nome' => 'Militar de Teste 2',
        'posto_graduacao' => 'Cabo',
        'opm_origem_id' => $opm->id,
        'municipio' => 'Município de Teste',
    ]);

    // 3. Criar o edital que será consultado
    $edital = Edital::create([
        'codigo' => 'EDITAL-LISTA-001',
        'titulo' => 'Edital para Teste de Listagem',
        'descricao' => 'Teste automatizado da listagem.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar outro edital para verificar o filtro
    $outroEdital = Edital::create([
        'codigo' => 'EDITAL-LISTA-002',
        'titulo' => 'Outro Edital',
        'descricao' => 'Edital que não deve aparecer na listagem.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 5. Criar a inscrição mais antiga do edital consultado
    Inscricao::create([
        'protocolo' => 'INS-LISTA-001',
        'edital_id' => $edital->id,
        'militar_id' => $militar1->id,
        'situacao' => 'SUBMETIDA',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => '2026-10-10 10:00:00',
        'concluded_at' => null,
    ]);

    // 6. Criar a inscrição mais recente do edital consultado
    Inscricao::create([
        'protocolo' => 'INS-LISTA-002',
        'edital_id' => $edital->id,
        'militar_id' => $militar2->id,
        'situacao' => 'SUBMETIDA',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => '2026-10-11 10:00:00',
        'concluded_at' => null,
    ]);

    // 7. Criar uma inscrição em outro edital
    Inscricao::create([
        'protocolo' => 'INS-LISTA-003',
        'edital_id' => $outroEdital->id,
        'militar_id' => $militar1->id,
        'situacao' => 'SUBMETIDA',
        'aceite_condicoes' => true,
        'version' => 1,
        'submitted_at' => '2026-10-12 10:00:00',
        'concluded_at' => null,
    ]);

    // 8. Consultar somente as inscrições do primeiro edital
    $usuarioSme = criarUsuarioSme();

    $response = $this->actingAs($usuarioSme, 'sanctum')
        ->getJson("/api/editais/{$edital->id}/inscricoes");

    // 9. Verificar a resposta e a quantidade de inscrições
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.protocolo', 'INS-LISTA-002')
        ->assertJsonPath('data.1.protocolo', 'INS-LISTA-001')
        ->assertJsonPath('data.0.edital.codigo', 'EDITAL-LISTA-001')
        ->assertJsonPath('data.1.edital.codigo', 'EDITAL-LISTA-001')
        ->assertJsonMissing([
            'protocolo' => 'INS-LISTA-003',
        ]);
});

test('a listagem de inscrições exige autenticação', function () {
    $edital = Edital::create([
        'codigo' => 'EDITAL-SEM-AUTH',
        'titulo' => 'Edital sem autenticação',
        'descricao' => 'Teste de autenticação.',
        'max_opcoes' => 1,
        'situacao' => 'ABERTO',
    ]);

    $this->getJson("/api/editais/{$edital->id}/inscricoes")
        ->assertUnauthorized();
});
test('um militar não pode listar as inscrições de um edital', function () {
    $opm = Opm::create([
        'codigo' => 'OPM-LISTA-MILITAR',
        'nome' => 'OPM de Teste',
        'municipio' => 'Porto Alegre',
    ]);

    $contextoMilitar = criarUsuarioMilitar($opm);

    $edital = Edital::create([
        'codigo' => 'EDITAL-LISTA-MILITAR',
        'titulo' => 'Edital para teste de autorização',
        'descricao' => 'Teste de permissão.',
        'max_opcoes' => 1,
        'situacao' => 'ABERTO',
    ]);

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->getJson("/api/editais/{$edital->id}/inscricoes")
        ->assertForbidden();
});
