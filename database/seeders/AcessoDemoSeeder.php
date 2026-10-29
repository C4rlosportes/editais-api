<?php

namespace Database\Seeders;

use App\Models\Militar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AcessoDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Contas de demonstração só podem ser criadas localmente
        // ou durante testes.
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'AcessoDemoSeeder só pode ser executado em local ou testing.'
            );
        }

        $senha = config('demo.access_password');

        if (! is_string($senha) || strlen($senha) < 12) {
            throw new RuntimeException(
                'Configure DEMO_ACCESS_PASSWORD no ambiente local '
                . 'com pelo menos 12 caracteres.'
            );
        }

        DB::transaction(function () use ($senha): void {
            $contasSme = [
                [
                    'name' => 'SME Demo 01',
                    'email' => 'sme01@exemplo.test',
                ],
                [
                    'name' => 'SME Demo 02',
                    'email' => 'sme02@exemplo.test',
                ],
                [
                    'name' => 'SME Demo 03',
                    'email' => 'sme03@exemplo.test',
                ],
            ];

            foreach ($contasSme as $dados) {
                $usuario = User::updateOrCreate(
                    ['email' => $dados['email']],
                    [
                        'name' => $dados['name'],
                        'password' => $senha,
                    ]
                );

                $usuario->forceFill([
                    'perfil_acesso' => 'SME',
                ])->save();
            }

            // Os dez cadastros funcionais precisam existir primeiro.
            for ($numero = 1; $numero <= 10; $numero++) {
                $sufixo = str_pad(
                    (string) $numero,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $identificador = "demo.militar{$sufixo}";

                $militar = Militar::query()
                    ->where('usuario_keycloak', $identificador)
                    ->firstOrFail();

                $usuario = User::updateOrCreate(
                    ['email' => "militar{$sufixo}@exemplo.test"],
                    [
                        'name' => $militar->nome,
                        'password' => $senha,
                    ]
                );

                $usuario->forceFill([
                    'perfil_acesso' => 'MILITAR',
                ])->save();

                $militar->user_id = $usuario->id;
                $militar->save();
            }
        });
    }
}
