<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Militar;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use App\Models\Historico;

uses(RefreshDatabase::class);

beforeEach(function () {
    $opm = Opm::create([
        'codigo' => 'OPM-AUTH-TESTE',
        'nome' => 'OPM de Autenticação',
        'municipio' => 'Porto Alegre',
    ]);

    $militar = Militar::create([
        'usuario_keycloak' => 'usuario-auth-teste',
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-AUTH-TESTE-001',
        'nome' => 'Militar Autenticado de Teste',
        'posto_graduacao' => 'Soldado',
        'opm_origem_id' => $opm->id,
        'municipio' => $opm->municipio,
    ]);

    $usuario = vincularUsuarioAoMilitar($militar);

    $this->actingAs($usuario, 'sanctum');
});

test('as migrations funcionam no banco de teste', function () {
    expect(Schema::hasTable('editais'))->toBeTrue()
        ->and(Schema::hasTable('inscricoes'))->toBeTrue()
        ->and(Schema::hasTable('escolhas'))->toBeTrue()
        ->and(Schema::hasTable('conferencias'))->toBeTrue();
});

test('um militar consegue criar uma inscrição com escolhas e conferências pendentes', function () {
    // 1. Criar uma OPM para os militares e destinos
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-001',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares para atender ao ID temporário
    // utilizado atualmente no InscricaoController: findOrFail(4).
    for ($i = 1; $i <= 4; $i++) {
        $militar = Militar::create([
            'usuario_keycloak' => "usuario-teste-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-TESTE-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-001',
        'titulo' => 'Edital para Testes',
        'descricao' => 'Edital criado durante o teste automatizado.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar um requisito para o edital
    $requisito = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de teste',
        'ordem' => 1,
    ]);

    // 5. Criar um destino disponível nesse edital
    $destino = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    // 6. Enviar a requisição para a API
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [$destino->id],
        ]
    );

    // 7. Verificar a resposta HTTP
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'protocolo',
                'situacao',
                'aceite_condicoes',
                'militar',
                'edital',
                'escolhas',
                'requisitos',
            ],
            'mensagem',
        ])
        ->assertJsonPath('data.situacao', 'SUBMETIDA')
        ->assertJsonPath('data.aceite_condicoes', true)
        ->assertJsonPath('mensagem', 'Inscrição criada com sucesso.');

    // 8. Verificar se a inscrição foi salva
    $this->assertDatabaseHas('inscricoes', [
        'edital_id' => $edital->id,
        'militar_id' => auth('sanctum')->user()->militar->id,
        'situacao' => 'SUBMETIDA',
        'version' => 1,
    ]);

    // 9. Verificar se a escolha foi salva na ordem correta
    $this->assertDatabaseHas('escolhas', [
        'destino_id' => $destino->id,
        'ordem' => 1,
    ]);

    // 10. Verificar se a conferência foi criada como PENDENTE
    $this->assertDatabaseHas('conferencias', [
        'requisito_id' => $requisito->id,
        'resultado' => 'PENDENTE',
    ]);

    // 11. Verificar as quantidades de registros criados
    $this->assertDatabaseCount('inscricoes', 1);
    $this->assertDatabaseCount('escolhas', 1);
    $this->assertDatabaseCount('conferencias', 1);

    // 12. Verificar o histórico da criação da inscrição
    $historico = Historico::query()
        ->where('edital_id', $edital->id)
        ->where('acao', 'INSCRICAO_CRIADA')
        ->first();

    expect($historico)->not->toBeNull();

    expect($historico->autor)->toBeNull();
    expect($historico->dados_anteriores)->toBeNull();

    expect($historico->dados_posteriores['inscricao_id'])
        ->toBe($response->json('data.id'));

    expect($historico->dados_posteriores['protocolo'])
        ->toBe($response->json('data.protocolo'));

    expect($historico->dados_posteriores['militar_id'])
        ->toBe(auth('sanctum')->user()->militar->id);

    expect($historico->dados_posteriores['situacao'])
        ->toBe('SUBMETIDA');

    expect($historico->dados_posteriores['aceite_condicoes'])
        ->toBeTrue();

    expect($historico->dados_posteriores['version'])
        ->toBe(1);

    expect($historico->dados_posteriores['destinos'])
        ->toBe([$destino->id]);

    expect($historico->dados_posteriores['requisitos'])
        ->toBe([$requisito->id]);

    $this->assertDatabaseCount('historicos', 1);
});

test('um militar não pode se inscrever duas vezes no mesmo edital', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-002',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares.
    // O controller ainda utiliza temporariamente o ID 4.
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-duplicidade-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-DUP-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-002',
        'titulo' => 'Edital de Teste de Duplicidade',
        'descricao' => 'Teste de inscrição duplicada.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar um requisito
    $requisito = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de teste',
        'ordem' => 1,
    ]);

    // 5. Criar um destino
    $destino = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    // 6. Preparar os dados da requisição
    $dados = [
        'aceite_condicoes' => true,
        'destinos' => [$destino->id],
    ];

    $url = "/api/editais/{$edital->id}/inscricoes";

    // 7. A primeira inscrição deve funcionar
    $primeiraResposta = $this->postJson($url, $dados);

    $primeiraResposta->assertCreated();

    // 8. A segunda inscrição deve ser rejeitada
    $segundaResposta = $this->postJson($url, $dados);

    $segundaResposta->assertUnprocessable();

    // 9. Confirmar que não foram criados registros duplicados
    $this->assertDatabaseCount('inscricoes', 1);
    $this->assertDatabaseCount('escolhas', 1);
    $this->assertDatabaseCount('conferencias', 1);
});

test('um militar não pode se inscrever em um edital que não esteja aberto', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-003',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares para o ID temporário usado pelo controller
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-status-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-STATUS-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Testar os três estados que não permitem novas inscrições
    foreach (['RASCUNHO', 'ENCERRADO', 'EM_ANALISE'] as $situacao) {
        $edital = Edital::create([
            'codigo' => "EDITAL-TESTE-{$situacao}",
            'titulo' => "Edital de Teste {$situacao}",
            'descricao' => 'Teste de bloqueio de inscrições.',
            'max_opcoes' => 3,
            'situacao' => $situacao,
        ]);

        // Criar um requisito para o edital
        Requisito::create([
            'edital_id' => $edital->id,
            'descricao' => 'Requisito de teste',
            'ordem' => 1,
        ]);

        // Criar um destino para o edital
        $destino = Destino::create([
            'edital_id' => $edital->id,
            'opm_id' => $opm->id,
            'vagas' => 2,
        ]);

        // Tentar realizar a inscrição
        $response = $this->postJson(
            "/api/editais/{$edital->id}/inscricoes",
            [
                'aceite_condicoes' => true,
                'destinos' => [$destino->id],
            ]
        );

        // A API deve rejeitar a inscrição
        $response->assertUnprocessable();
    }

    // Nenhuma inscrição ou registro relacionado deve ter sido criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode escolher mais destinos do que o limite do edital', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-004',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares.
    // O controller ainda utiliza temporariamente o ID 4.
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-limite-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-LIMITE-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital que permite no máximo três opções
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-LIMITE',
        'titulo' => 'Edital de Teste do Limite',
        'descricao' => 'Teste da quantidade máxima de escolhas.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar quatro destinos para o mesmo edital
    $destinos = [];

    for ($i = 1; $i <= 4; $i++) {
        $destino = Destino::create([
            'edital_id' => $edital->id,
            'opm_id' => $opm->id,
            'vagas' => 2,
        ]);

        $destinos[] = $destino->id;
    }

    // 5. Tentar escolher quatro destinos, embora o limite seja três
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => $destinos,
        ]
    );

    // 6. A API deve rejeitar a inscrição
    $response->assertUnprocessable();

    // 7. Nenhuma inscrição, escolha ou conferência deve ser criada
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode escolher um destino de outro edital', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-005',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares, devido ao ID temporário usado
    // atualmente pelo InscricaoController.
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-destino-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-DESTINO-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar o edital no qual o militar tentará se inscrever
    $editalInscricao = Edital::create([
        'codigo' => 'EDITAL-TESTE-DESTINO-A',
        'titulo' => 'Edital de Inscrição',
        'descricao' => 'Edital utilizado no teste.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar outro edital
    $outroEdital = Edital::create([
        'codigo' => 'EDITAL-TESTE-DESTINO-B',
        'titulo' => 'Outro Edital',
        'descricao' => 'Edital proprietário do destino.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 5. Criar um destino que pertence ao segundo edital
    $destinoDeOutroEdital = Destino::create([
        'edital_id' => $outroEdital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    // 6. Tentar usar esse destino na inscrição do primeiro edital
    $response = $this->postJson(
        "/api/editais/{$editalInscricao->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [$destinoDeOutroEdital->id],
        ]
    );

    // 7. A API deve rejeitar a requisição
    $response->assertUnprocessable();

    // 8. A tentativa inválida não pode gerar registros
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode se inscrever sem aceitar as condições', function () {
    // 1. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-ACEITE',
        'titulo' => 'Edital de Teste do Aceite',
        'descricao' => 'Teste de aceitação das condições.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 2. Enviar uma requisição sem aceitar as condições
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => false,
            'destinos' => [999],
        ]
    );

    // 3. Verificar se a API rejeitou a requisição
    $response->assertUnprocessable();

    // 4. Confirmar que nenhum registro foi criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode se inscrever sem escolher destinos', function () {
    // 1. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-SEM-DESTINO',
        'titulo' => 'Edital de Teste sem Destino',
        'descricao' => 'Teste de validação das escolhas.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 2. Enviar a requisição sem escolher nenhum destino
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [],
        ]
    );

    // 3. A API deve rejeitar a requisição
    $response->assertUnprocessable();

    // 4. Nenhum registro de inscrição deve ter sido criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode escolher o mesmo destino duas vezes', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-011',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-DUPLICIDADE-DESTINO',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Teste de destinos duplicados.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 3. Criar um destino válido para esse edital
    $destino = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    // 4. Enviar o mesmo destino duas vezes
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [
                $destino->id,
                $destino->id,
            ],
        ]
    );

    // 5. A API deve rejeitar a lista duplicada
    $response->assertUnprocessable();

    // 6. Nenhum registro da inscrição deve ser criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('a API rejeita identificadores de destino que não sejam inteiros', function () {
    // 1. Criar um edital aberto para o teste
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-ID-INVALIDO',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Teste de validação dos identificadores.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 2. Enviar um identificador inválido como destino
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => ['abc'],
        ]
    );

    // 3. A API deve rejeitar a requisição
    $response->assertUnprocessable();

    // 4. Confirmar que o erro está no primeiro item de destinos
    $response->assertJsonValidationErrors(['destinos.0']);

    // 5. Nenhum registro deve ter sido criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('um militar não pode escolher um destino que não existe', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-012',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares para atender ao ID temporário
    // utilizado pelo InscricaoController: findOrFail(4).
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-inexistente-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-INEX-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-DESTINO-INEXISTENTE',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Teste de destino inexistente.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Tentar escolher o destino de ID 999, que não foi criado
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [999],
        ]
    );

    // 5. A API deve rejeitar a inscrição
    $response->assertUnprocessable();

    // 6. Nenhum registro relacionado à inscrição deve ser criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('a inscrição mantém a ordem das escolhas e cria uma conferência para cada requisito', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-013',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares para atender ao ID temporário
    // utilizado atualmente pelo InscricaoController: findOrFail(4).
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-ordem-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-ORDEM-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital aberto que permite três escolhas
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-ORDEM',
        'titulo' => 'Edital de Teste da Ordem',
        'descricao' => 'Teste da ordem das escolhas e das conferências.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar dois requisitos para o edital
    $requisito1 = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Primeiro requisito de teste',
        'ordem' => 1,
    ]);

    $requisito2 = Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Segundo requisito de teste',
        'ordem' => 2,
    ]);

    // 5. Criar três destinos válidos
    $destino1 = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    $destino2 = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 3,
    ]);

    $destino3 = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 1,
    ]);

    // 6. Enviar os destinos na ordem de preferência desejada
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [
                $destino3->id,
                $destino1->id,
                $destino2->id,
            ],
        ]
    );

    // 7. Verificar que a inscrição foi criada
    $response->assertCreated();

    // 8. Verificar se a ordem enviada foi preservada
    $this->assertDatabaseHas('escolhas', [
        'destino_id' => $destino3->id,
        'ordem' => 1,
    ]);

    $this->assertDatabaseHas('escolhas', [
        'destino_id' => $destino1->id,
        'ordem' => 2,
    ]);

    $this->assertDatabaseHas('escolhas', [
        'destino_id' => $destino2->id,
        'ordem' => 3,
    ]);

    // 9. Verificar se cada requisito recebeu uma conferência pendente
    $this->assertDatabaseHas('conferencias', [
        'requisito_id' => $requisito1->id,
        'resultado' => 'PENDENTE',
    ]);

    $this->assertDatabaseHas('conferencias', [
        'requisito_id' => $requisito2->id,
        'resultado' => 'PENDENTE',
    ]);

    // 10. Conferir as quantidades criadas
    $this->assertDatabaseCount('inscricoes', 1);
    $this->assertDatabaseCount('escolhas', 3);
    $this->assertDatabaseCount('conferencias', 2);
});

test('a API retorna os dados completos de uma inscrição consultada', function () {
    // 1. Criar uma OPM
    $opm = Opm::create([
        'codigo' => 'OPM-TESTE-014',
        'nome' => 'OPM de Teste',
        'municipio' => 'Município de Teste',
    ]);

    // 2. Criar quatro militares para atender ao ID temporário
    // utilizado atualmente no InscricaoController: findOrFail(4).
    for ($i = 1; $i <= 4; $i++) {
        Militar::create([
            'usuario_keycloak' => "usuario-consulta-{$i}",
            'perfil' => 'MILITAR',
            'id_funcional' => "MIL-CONSULTA-00{$i}",
            'nome' => "Militar de Teste {$i}",
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);
    }

    // 3. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-CONSULTA',
        'titulo' => 'Edital para Consulta',
        'descricao' => 'Teste de consulta de inscrição.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 4. Criar um requisito
    Requisito::create([
        'edital_id' => $edital->id,
        'descricao' => 'Requisito de consulta',
        'ordem' => 1,
    ]);

    // 5. Criar um destino
    $destino = Destino::create([
        'edital_id' => $edital->id,
        'opm_id' => $opm->id,
        'vagas' => 2,
    ]);

    // 6. Criar a inscrição pela API
    $criacao = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [$destino->id],
        ]
    );

    $criacao->assertCreated();

    // 7. Recuperar o ID da inscrição criada
    $inscricaoId = $criacao->json('data.id')
        ?? $criacao->json('id');

    expect($inscricaoId)->not->toBeNull();

    // 8. Consultar a inscrição pelo endpoint GET
    $consulta = $this->getJson("/api/inscricoes/{$inscricaoId}");

    // 9. Verificar o status HTTP e a estrutura da resposta
    $consulta->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'protocolo',
                'situacao',
                'aceite_condicoes',
                'militar' => [
                    'nome',
                    'posto_graduacao',
                    'id_funcional',
                    'opm' => [
                        'nome',
                        'municipio',
                    ],
                ],
                'edital' => [
                    'codigo',
                    'titulo',
                ],
                'escolhas' => [
                    [
                        'ordem',
                        'destino' => [
                            'opm',
                            'municipio',
                        ],
                    ],
                ],
                'requisitos' => [
                    [
                        'ordem',
                        'descricao',
                        'resultado',
                    ],
                ],
            ],
        ]);

    // 10. Conferir valores importantes da resposta
    $consulta->assertJsonPath('data.situacao', 'SUBMETIDA')
        ->assertJsonPath('data.edital.codigo', 'EDITAL-TESTE-CONSULTA')
        ->assertJsonPath('data.escolhas.0.ordem', 1)
        ->assertJsonPath('data.requisitos.0.resultado', 'PENDENTE');
});

test('a API retorna 404 ao consultar uma inscrição inexistente', function () {
    // Tentar consultar uma inscrição que não existe
    $response = $this->getJson('/api/inscricoes/999999');

    // A API deve retornar HTTP 404 Not Found
    $response->assertNotFound();
});

test('a API rejeita uma inscrição sem o campo destinos', function () {
    // 1. Criar um edital aberto
    $edital = Edital::create([
        'codigo' => 'EDITAL-TESTE-SEM-CAMPO',
        'titulo' => 'Edital de Teste',
        'descricao' => 'Teste de campo obrigatório.',
        'max_opcoes' => 3,
        'situacao' => 'ABERTO',
    ]);

    // 2. Enviar a requisição sem informar destinos
    $response = $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
        ]
    );

    // 3. Verificar se a API rejeitou a requisição
    $response->assertUnprocessable();

    // 4. Confirmar que o erro está no campo destinos
    $response->assertJsonValidationErrors(['destinos']);

    // 5. Verificar que nenhum registro foi criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});

test('a API retorna 404 ao tentar se inscrever em um edital inexistente', function () {
    // Tentar criar uma inscrição para um edital que não existe
    $response = $this->postJson(
        '/api/editais/999999/inscricoes',
        [
            'aceite_condicoes' => true,
            'destinos' => [1],
        ]
    );

    // O Laravel deve retornar 404 Not Found
    $response->assertNotFound();

    // Nenhum registro de inscrição deve ser criado
    $this->assertDatabaseCount('inscricoes', 0);
    $this->assertDatabaseCount('escolhas', 0);
    $this->assertDatabaseCount('conferencias', 0);
});
