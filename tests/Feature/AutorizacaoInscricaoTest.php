<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\Opm;
use App\Models\Requisito;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

function criarContextoAutorizacaoInscricao(): array
{
    $opm = Opm::create([
        'codigo' => 'OPM-AUT-' . str()->uuid(),
        'nome' => 'OPM de Autorização',
        'municipio' => 'Porto Alegre',
    ]);

    $edital = Edital::create([
        'codigo' => 'EDITAL-AUT-' . str()->uuid(),
        'titulo' => 'Edital para teste de autorização',
        'descricao' => 'Edital usado nos testes de segurança.',
        'max_opcoes' => 1,
        'situacao' => 'ABERTO',
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

    return [$edital, $destino, $opm];
}

/**
 * Cria uma inscrição utilizando o endpoint real da API.
 */
function criarInscricaoParaTeste(
    $teste,
    User $usuario,
    Edital $edital,
    Destino $destino
): Inscricao {
    $response = $teste->actingAs($usuario, 'sanctum')
        ->postJson(
            "/api/editais/{$edital->id}/inscricoes",
            [
                'aceite_condicoes' => true,
                'destinos' => [$destino->id],
            ]
        );

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id'],
        ]);

    return Inscricao::findOrFail($response->json('data.id'));
}

test('criar inscrição exige autenticação', function () {
    [$edital, $destino] = criarContextoAutorizacaoInscricao();

    $this->postJson(
        "/api/editais/{$edital->id}/inscricoes",
        [
            'aceite_condicoes' => true,
            'destinos' => [$destino->id],
        ]
    )->assertUnauthorized();
});

test('uma conta SME não pode criar inscrição', function () {
    [$edital, $destino] = criarContextoAutorizacaoInscricao();

    $usuario = criarUsuarioSme();

    $this->actingAs($usuario, 'sanctum')
        ->postJson(
            "/api/editais/{$edital->id}/inscricoes",
            [
                'aceite_condicoes' => true,
                'destinos' => [$destino->id],
            ]
        )
        ->assertForbidden();
});

test('consultar inscrição exige autenticação', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $inscricao = criarInscricaoParaTeste(
        $this,
        $contextoMilitar['user'],
        $edital,
        $destino
    );

    Auth::guard('sanctum')->forgetUser();

    $this->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertUnauthorized();
});

test('um militar consegue consultar sua própria inscrição', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $inscricao = criarInscricaoParaTeste(
        $this,
        $contextoMilitar['user'],
        $edital,
        $destino
    );

    $this->actingAs($contextoMilitar['user'], 'sanctum')
        ->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $inscricao->id);
});

test('um militar não pode consultar a inscrição de outro militar', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $militarProprietario = criarUsuarioMilitar($opm);
    $outroMilitar = criarUsuarioMilitar($opm);

    $inscricao = criarInscricaoParaTeste(
        $this,
        $militarProprietario['user'],
        $edital,
        $destino
    );

    $this->actingAs($outroMilitar['user'], 'sanctum')
        ->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertForbidden();
});

test('uma conta SME consegue consultar qualquer inscrição', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $contextoMilitar = criarUsuarioMilitar($opm);
    $usuarioSme = criarUsuarioSme();

    $inscricao = criarInscricaoParaTeste(
        $this,
        $contextoMilitar['user'],
        $edital,
        $destino
    );

    $this->actingAs($usuarioSme, 'sanctum')
        ->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $inscricao->id);
});

test('uma conta sem perfil autorizado não pode consultar inscrição', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $inscricao = criarInscricaoParaTeste(
        $this,
        $contextoMilitar['user'],
        $edital,
        $destino
    );

    $usuario = User::factory()->create();

    $usuario->forceFill([
        'perfil_acesso' => 'SEM_ACESSO',
    ])->save();

    $this->actingAs($usuario, 'sanctum')
        ->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertForbidden();
});

test('um militar sem cadastro vinculado não pode consultar inscrição', function () {
    [$edital, $destino, $opm] = criarContextoAutorizacaoInscricao();

    $contextoMilitar = criarUsuarioMilitar($opm);

    $inscricao = criarInscricaoParaTeste(
        $this,
        $contextoMilitar['user'],
        $edital,
        $destino
    );

    $usuarioSemVinculo = User::factory()->create();

    $usuarioSemVinculo->forceFill([
        'perfil_acesso' => 'MILITAR',
    ])->save();

    $this->actingAs($usuarioSemVinculo, 'sanctum')
        ->getJson("/api/inscricoes/{$inscricao->id}")
        ->assertForbidden();
});
