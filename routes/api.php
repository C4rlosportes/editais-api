<?php

use App\Http\Controllers\EditalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\OpmController;
use App\Http\Controllers\HistoricoController;

Route::post('/editais/{edital}/inscricoes', [InscricaoController::class, 'store']); #Aqui está sendo usado o Route Model Binding para o Edital, então o parâmetro {edital} será automaticamente
#resolvido para uma instância do modelo Edital com base no ID fornecido na URL.

Route::get('/inscricoes/{inscricao}', [InscricaoController::class, 'show']); #Aqui está sendo usado o Route Model Binding para a Inscrição, então o parâmetro {inscricao} será automaticamente
#resolvido para uma instância do modelo Inscricao com base no ID fornecido na URL.

Route::get('/editais', [EditalController::class, 'index']); #Lista os editais cadastrados, chamando o método index do EditalController.

Route::get('/editais/{edital}', [EditalController::class, 'show']); #Consulta um edital específico, chamando o método show do EditalController. O parâmetro {edital} será resolvido para uma instância do modelo Edital com base no ID fornecido na URL.

Route::get(
    '/editais/{edital}/inscricoes',
    [InscricaoController::class, 'index'] #Lista as inscrições de um edital específico, chamando o método index do InscricaoController. O parâmetro {edital} será resolvido para uma instância do modelo Edital com base no ID fornecido na URL.
);

Route::patch(
    '/inscricoes/{inscricao}/conferencias/{conferencia}',
    [InscricaoController::class, 'atualizarConferencia'] #Atualiza uma conferência específica de uma inscrição, chamando o método atualizarConferencia do InscricaoController. Os parâmetros {inscricao} e {conferencia} serão resolvidos para instâncias dos modelos Inscricao e Conferencia, respectivamente, com base nos IDs fornecidos na URL.
);

Route::put('/editais/{edital}', [EditalController::class, 'update']); #Atualiza a situação de um edital específico, chamando o método update do EditalController. O parâmetro {edital} será resolvido para uma instância do modelo Edital com base no ID fornecido na URL.

Route::put(
    '/inscricoes/{inscricao}/analise',
    [InscricaoController::class, 'atualizarAnalise'] #Atualiza a análise de uma inscrição específica, chamando o método atualizarAnalise do InscricaoController. O parâmetro {inscricao} será resolvido para uma instância do modelo Inscricao com base no ID fornecido na URL.
);

Route::post('/editais', [EditalController::class, 'store']); #Cria um novo edital, chamando o método store do EditalController. Os dados do edital serão validados pelo CriarEditalRequest antes de serem processados pelo serviço de criação de editais.

Route::get('/opms', [OpmController::class, 'index']); #Lista as OPMs cadastradas, chamando o método index do OpmController.

Route::put(
    '/editais/{edital}/conteudo',
    [EditalController::class, 'atualizarConteudo']
); #Atualiza o conteúdo de um edital específico, chamando o método atualizarConteudo do EditalController. O parâmetro {edital} será resolvido para uma instância do modelo Edital com base no ID fornecido na URL. Os dados do edital serão validados pelo AtualizarEditalRequest antes de serem processados pelo serviço de atualização de editais.

Route::get(
    '/editais/{edital}/historico',
    [HistoricoController::class, 'index']
); #Lista o histórico de um edital específico, chamando o método index do HistoricoController. O parâmetro {edital} será resolvido para uma instância do modelo Edital com base no ID fornecido na URL.
