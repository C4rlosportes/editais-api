<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerfilMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$perfis
    ): Response {
        $usuario = $request->user();

        // Verifica se existe um usuário autenticado.
        if (! $usuario) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Verifica se o perfil do usuário está entre os permitidos.
        if (! in_array($usuario->perfil_acesso, $perfis, true)) {
            return response()->json([
                'message' => 'Acesso não autorizado.',
            ], 403);
        }

        return $next($request);
    }
}
