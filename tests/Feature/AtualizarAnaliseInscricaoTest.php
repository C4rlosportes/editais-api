<?php

namespace Tests\Feature;

use App\Models\Conferencia;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\Militar;
use App\Models\Opm;
use App\Models\Requisito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Historico;

class AtualizarAnaliseInscricaoTest extends TestCase
{
    use RefreshDatabase;

    private function criarCenario(
        string $situacaoEdital,
        string $situacaoInscricao,
        array $resultados = ['PENDENTE']
    ): Inscricao {
        $opm = Opm::create([
            'codigo' => 'OPM-TESTE-001',
            'nome' => 'OPM de Teste',
            'municipio' => 'Município de Teste',
        ]);

        $militar = Militar::create([
            'usuario_keycloak' => 'demo.teste',
            'perfil' => 'MILITAR',
            'id_funcional' => 'MIL-TESTE-001',
            'nome' => 'Militar de Teste',
            'posto_graduacao' => 'Soldado',
            'opm_origem_id' => $opm->id,
            'municipio' => 'Município de Teste',
        ]);

        $edital = Edital::create([
            'codigo' => 'EDITAL-TESTE-001',
            'titulo' => 'Edital de Teste',
            'descricao' => 'Edital para testes de análise.',
            'max_opcoes' => 3,
            'situacao' => $situacaoEdital,
        ]);

        $inscricao = Inscricao::create([
            'protocolo' => 'INS-TESTE-001',
            'edital_id' => $edital->id,
            'militar_id' => $militar->id,
            'situacao' => $situacaoInscricao,
            'aceite_condicoes' => true,
            'version' => 1,
            'submitted_at' => now(),
            'concluded_at' => null,
        ]);

        foreach ($resultados as $indice => $resultado) {
            $requisito = Requisito::create([
                'edital_id' => $edital->id,
                'descricao' => 'Requisito de teste ' . ($indice + 1),
                'ordem' => $indice + 1,
            ]);

            Conferencia::create([
                'inscricao_id' => $inscricao->id,
                'requisito_id' => $requisito->id,
                'resultado' => $resultado,
                'observacao' => $resultado === 'NAO_ATENDE'
                    ? 'Justificativa de teste.'
                    : null,
            ]);
        }

        return $inscricao;
    }

    public function test_pode_iniciar_analise_quando_edital_esta_em_analise(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'SUBMETIDA'
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'EM_ANALISE']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'EM_ANALISE');

        $this->assertDatabaseHas('inscricoes', [
            'id' => $inscricao->id,
            'situacao' => 'EM_ANALISE',
        ]);
    }

    public function test_nao_pode_iniciar_analise_se_edital_nao_esta_em_analise(): void
    {
        $inscricao = $this->criarCenario(
            'ENCERRADO',
            'SUBMETIDA'
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'EM_ANALISE']
        );

        $response->assertStatus(409);

        $this->assertDatabaseHas('inscricoes', [
            'id' => $inscricao->id,
            'situacao' => 'SUBMETIDA',
        ]);
    }

    public function test_nao_pode_concluir_com_conferencia_pendente(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'EM_ANALISE',
            ['PENDENTE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertStatus(409);

        $this->assertDatabaseHas('inscricoes', [
            'id' => $inscricao->id,
            'situacao' => 'EM_ANALISE',
        ]);
        $this->assertDatabaseCount('historicos', 0);
    }

    public function test_conclui_como_apta_quando_todas_conferencias_atendem(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'EM_ANALISE',
            ['ATENDE', 'ATENDE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'APTA');

        $inscricaoAtualizada = $inscricao->fresh();

        $this->assertNotNull($inscricaoAtualizada->concluded_at);
        $this->assertSame(2, (int) $inscricaoAtualizada->version);
    }

    public function test_conclui_como_inapta_se_alguma_conferencia_nao_atende(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'EM_ANALISE',
            ['ATENDE', 'NAO_ATENDE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'INAPTA');

        $this->assertNotNull($inscricao->fresh()->concluded_at);
    }

    public function test_nao_pode_concluir_inscricao_que_nao_esta_em_analise(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'SUBMETIDA',
            ['ATENDE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertStatus(409);

        $this->assertDatabaseHas('inscricoes', [
            'id' => $inscricao->id,
            'situacao' => 'SUBMETIDA',
        ]);
    }

    public function test_registra_historico_ao_iniciar_analise(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'SUBMETIDA'
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'EM_ANALISE']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'EM_ANALISE');

        $this->assertDatabaseCount('historicos', 1);

        $historico = Historico::query()
            ->where('edital_id', $inscricao->edital_id)
            ->firstOrFail();

        $this->assertSame('ANALISE_INICIADA', $historico->acao);
        $this->assertNull($historico->autor);

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'SUBMETIDA',
                'version' => 1,
            ],
            $historico->dados_anteriores
        );

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'EM_ANALISE',
                'version' => 2,
            ],
            $historico->dados_posteriores
        );
    }
    public function test_registra_historico_ao_concluir_inscricao_como_apta(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'EM_ANALISE',
            ['ATENDE', 'ATENDE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'APTA');

        $this->assertDatabaseCount('historicos', 1);

        $historico = Historico::query()
            ->where('edital_id', $inscricao->edital_id)
            ->firstOrFail();

        $this->assertSame('ANALISE_CONCLUIDA', $historico->acao);
        $this->assertNull($historico->autor);

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'EM_ANALISE',
                'version' => 1,
            ],
            $historico->dados_anteriores
        );

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'APTA',
                'version' => 2,
            ],
            $historico->dados_posteriores
        );
    }
    public function test_registra_historico_ao_concluir_inscricao_como_inapta(): void
    {
        $inscricao = $this->criarCenario(
            'EM_ANALISE',
            'EM_ANALISE',
            ['ATENDE', 'NAO_ATENDE']
        );

        $response = $this->putJson(
            "/api/inscricoes/{$inscricao->id}/analise",
            ['situacao' => 'CONCLUIDA']
        );

        $response->assertOk()
            ->assertJsonPath('data.situacao', 'INAPTA');

        $this->assertDatabaseCount('historicos', 1);

        $historico = Historico::query()
            ->where('edital_id', $inscricao->edital_id)
            ->firstOrFail();

        $this->assertSame('ANALISE_CONCLUIDA', $historico->acao);
        $this->assertNull($historico->autor);

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'EM_ANALISE',
                'version' => 1,
            ],
            $historico->dados_anteriores
        );

        $this->assertSame(
            [
                'inscricao_id' => $inscricao->id,
                'situacao' => 'INAPTA',
                'version' => 2,
            ],
            $historico->dados_posteriores
        );
    }
}
