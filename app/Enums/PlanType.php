<?php declare(strict_types = 1);

namespace App\Enums;

/**
 * Tipos de planos disponíveis no sistema
 *
 * Este enum define os diferentes planos de assinatura disponíveis
 * com seus respectivos limites de requisições por minuto.
 */
enum PlanType: string
{
    case FREE         = 'free';
    case BASIC        = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE   = 'enterprise';
    case UNLIMITED    = 'unlimited';

    /**
     * Retorna o limite de requisições por minuto para o plano
     */
    public function requestsPerMinute(): int
    {
        return match($this) {
            self::FREE         => 30,          // 30 req/min (0.5 req/s)
            self::BASIC        => 60,         // 60 req/min (1 req/s)
            self::PROFESSIONAL => 300, // 300 req/min (5 req/s)
            self::ENTERPRISE   => 1200,  // 1200 req/min (20 req/s)
            self::UNLIMITED    => 0,      // Sem limite (0 significa ilimitado)
        };
    }

    /**
     * Retorna o número máximo de requisições simultâneas permitidas para o plano
     */
    public function maxConcurrentRequests(): int
    {
        return match($this) {
            self::FREE         => 5,
            self::BASIC        => 10,
            self::PROFESSIONAL => 25,
            self::ENTERPRISE   => 50,
            self::UNLIMITED    => 100,
        };
    }

    /**
     * Retorna se o plano tem acesso a uma determinada feature
     */
    public function hasFeature(string $feature): bool
    {
        // Mapeamento de recursos por plano
        $features = [
            'export_data'        => [self::PROFESSIONAL, self::ENTERPRISE, self::UNLIMITED],
            'api_tokens'         => [self::BASIC, self::PROFESSIONAL, self::ENTERPRISE, self::UNLIMITED],
            'advanced_analytics' => [self::ENTERPRISE, self::UNLIMITED],
            // Adicione mais features conforme necessário
        ];

        if (!isset($features[$feature])) {
            return false;
        }

        return in_array($this, $features[$feature], true);
    }
}
