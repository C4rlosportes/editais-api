<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\Edital;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Historico;

class AtualizarSituacaoEditalTest extends TestCase
{
    use RefreshDatabase;

    private function criarEdital(string $situacao): Edital
    {
        return Edital::create([
            'codigo' => 'EDITAL-TESTE-001',
            'titulo' => 'Edital de Teste',
            'descricao' => 'Edital usado nos testes automatizados.',
            'max_opcoes' => 3,
            'situacao' => $situacao,
        ]);
    }

    private function criarRequisito(Edital $edital): void
    {
        Requisito::create([
            'edital_id' => $edital->id,
            'descricao' => 'Requisito de teste',
            'ordem' => 1,
        ]);
    }

    private function criarDestino(Edital $edital): void
    {
        $opm = Opm::create([
            'codigo' => 'OPM-TESTE-001',
            'nome' => 'OPM de Teste',
            'municipio' => 'Município de Teste',
        ]);

        Destino::create([
            'edital_id' => $edital->id,
            'opm_id' => $opm->id,
            'vagas' => 3,
        ]);
    }

    public function test_pode_abrir_edital_com_requisito_e_destino(): void
    {
        $edital = $this->criarEdital('RASCUNHO');

        $this->criarRequisito($edital);
        $this->criarDestino($edital);

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'ABERTO');

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'situacao' => 'ABERTO',
        ]);
    }

    public function test_nao_pode_abrir_edital_sem_requisitos(): void
    {
        $edital = $this->criarEdital('RASCUNHO');

        $this->criarDestino($edital);

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'situacao' => 'RASCUNHO',
        ]);
    }

    public function test_nao_pode_abrir_edital_sem_destinos(): void
    {
        $edital = $this->criarEdital('RASCUNHO');

        $this->criarRequisito($edital);

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'situacao' => 'RASCUNHO',
        ]);
    }

    public function test_pode_encerrar_edital_aberto(): void
    {
        $edital = $this->criarEdital('ABERTO');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ENCERRADO']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'ENCERRADO');
    }

    public function test_pode_avancar_edital_encerrado_para_em_analise(): void
    {
        $edital = $this->criarEdital('ENCERRADO');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'EM_ANALISE']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'EM_ANALISE');
    }

    public function test_nao_pode_retroceder_a_situacao_do_edital(): void
    {
        $edital = $this->criarEdital('ABERTO');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'RASCUNHO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'situacao' => 'ABERTO',
        ]);
    }

    public function test_edital_em_analise_nao_pode_avancar_novamente(): void
    {
        $edital = $this->criarEdital('EM_ANALISE');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ENCERRADO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');
    }

    public function test_rejeita_situacao_inexistente(): void
    {
        $edital = $this->criarEdital('RASCUNHO');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'CANCELADO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');
    }

    public function test_nao_pode_repetir_a_situacao_atual_do_edital(): void
    {
        $edital = $this->criarEdital('ABERTO');

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('situacao');

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'situacao' => 'ABERTO',
        ]);
    }

    public function test_registra_historico_ao_alterar_situacao_do_edital(): void
    {
        $edital = $this->criarEdital('RASCUNHO');

        $this->criarRequisito($edital);
        $this->criarDestino($edital);

        $response = $this->putJson(
            "/api/editais/{$edital->id}",
            ['situacao' => 'ABERTO']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'ABERTO');

        $this->assertDatabaseCount('historicos', 1);

        $historico = Historico::query()
            ->where('edital_id', $edital->id)
            ->firstOrFail();

        $this->assertSame('SITUACAO_ALTERADA', $historico->acao);
        $this->assertNull($historico->autor);

        $this->assertSame(
            ['situacao' => 'RASCUNHO'],
            $historico->dados_anteriores
        );

        $this->assertSame(
            ['situacao' => 'ABERTO'],
            $historico->dados_posteriores
        );
    }
}
