<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\Events\ImpersonationActionPerformed;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Se o usuário não está autenticado, pular o middleware
        if (!$user) {
            return $next($request);
        }

        /** @var User $user */
        // Verificar se o token atual é de impersonation
        $isImpersonated = $request->bearerToken() && $user->tokens()
            ->where('token', hash('sha256', $request->bearerToken()))
            ->where('name', 'impersonation-token')
            ->exists();

        if ($isImpersonated) {
            // Verificar se a impersonation ainda está ativa
            $impersonation = $user->activeBeingImpersonated();

            if (!$impersonation) {
                // Se a impersonation foi encerrada, revogar o token
                $user->tokens()->where('name', 'impersonation-token')->delete();

                return response()->json([
                    'message' => 'Sessão de impersonation encerrada pelo impersonator.',
                ], 401);
            }

            // Adicionar informações de impersonation ao request para uso posterior
            $request->attributes->add([
                'is_impersonated'  => true,
                'impersonator_id'  => $impersonation->impersonator_id,
                'impersonation_id' => $impersonation->id,
            ]);

            // Disparar evento para auditoria assíncrona
            event(new ImpersonationActionPerformed(
                user: $user,
                impersonation: $impersonation,
                action: $request->method(),
                url: $request->fullUrl(),
                ip: (string) $request->ip(),
                userAgent: $request->userAgent()
            ));
        }

        return $next($request);
    }
}
