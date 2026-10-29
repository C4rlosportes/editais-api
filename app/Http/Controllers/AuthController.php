<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Autentica uma conta e gera um token de acesso.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('militar')
            ->where('email', $credentials['email'])
            ->first();

        if (
            ! $user ||
            ! Hash::check($credentials['password'], $user->password)
        ) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        if (! in_array($user->perfil_acesso, ['SME', 'MILITAR'], true)) {
            return response()->json([
                'message' => 'Esta conta não possui acesso ao sistema.',
            ], 403);
        }

        if (
            $user->perfil_acesso === 'MILITAR' &&
            ! $user->militar
        ) {
            return response()->json([
                'message' => 'Esta conta não está vinculada a um cadastro de militar.',
            ], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Retorna os dados da conta autenticada.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('militar');

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Revoga o token utilizado na requisição.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        // Limpa o usuário armazenado no guard de autenticação.
        Auth::guard('sanctum')->forgetUser();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Define quais dados da conta podem ser retornados pela API.
     */
    private function userPayload(User $user): array
    {
        $dados = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'perfil_acesso' => $user->perfil_acesso,
            'militar' => null,
        ];

        if ($user->perfil_acesso === 'MILITAR' && $user->militar) {
            $militar = $user->militar;

            $dados['militar'] = [
                'id' => $militar->id,
                'id_funcional' => $militar->id_funcional,
                'nome' => $militar->nome,
                'posto_graduacao' => $militar->posto_graduacao,
                'opm_origem_id' => $militar->opm_origem_id,
            ];
        }

        return $dados;
    }
}
