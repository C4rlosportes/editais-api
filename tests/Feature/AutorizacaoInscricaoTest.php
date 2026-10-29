
<?php

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    return [$edital, $destino];
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
