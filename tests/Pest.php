<?php

use Tests\TestCase;
use App\Models\Militar;
use App\Models\Opm;
use App\Models\User;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/


/**
 * Cria uma conta de teste com perfil SME.
 */
function criarUsuarioSme(array $atributos = []): User
{
    $identificador = (string) Str::uuid();

    $usuario = User::factory()->create([
        'name' => $atributos['name'] ?? 'Usuário SME de teste',
        'email' => $atributos['email']
            ?? "sme-{$identificador}@example.test",
    ]);

    $usuario->forceFill([
        'perfil_acesso' => 'SME',
    ])->save();

    return $usuario;
}

/**
 * Cria uma conta de militar e vincula seu cadastro funcional.
 *
 * @return array{user: User, militar: Militar}
 */
function criarUsuarioMilitar(Opm $opm, array $atributos = []): array
{
    $identificador = (string) Str::uuid();
    $nome = $atributos['nome'] ?? 'Militar de teste';

    $usuario = User::factory()->create([
        'name' => $nome,
        'email' => $atributos['email']
            ?? "militar-{$identificador}@example.test",
    ]);

    $usuario->forceFill([
        'perfil_acesso' => 'MILITAR',
    ])->save();

    $militar = Militar::create([
        'usuario_keycloak' => "teste-{$identificador}",
        'perfil' => 'MILITAR',
        'id_funcional' => 'MIL-' .
            strtoupper(str_replace('-', '', $identificador)),
        'nome' => $nome,
        'posto_graduacao' => $atributos['posto_graduacao'] ?? 'Soldado',
        'opm_origem_id' => $opm->id,
        'municipio' => $opm->municipio,
    ]);

    $militar->user_id = $usuario->id;
    $militar->save();

    return [
        'user' => $usuario,
        'militar' => $militar,
    ];
}

/**
 * Cria uma conta de acesso e a vincula a um militar existente.
 */
function vincularUsuarioAoMilitar(
    Militar $militar,
    array $atributos = []
): User {
    $identificador = (string) Str::uuid();

    $usuario = User::factory()->create([
        'name' => $atributos['name'] ?? $militar->nome,
        'email' => $atributos['email']
            ?? "militar-{$identificador}@example.test",
    ]);

    $usuario->forceFill([
        'perfil_acesso' => 'MILITAR',
    ])->save();

    $militar->user_id = $usuario->id;
    $militar->save();

    return $usuario;
}
