<?php declare(strict_types = 1);

namespace App\Enums;

/**
 * Tipos de feature flags disponíveis no sistema
 *
 * Este enum define os diferentes tipos de feature flags que podem ser
 * usados para habilitar/desabilitar funcionalidades de forma dinâmica.
 */
enum FeatureFlagType: string
{
    case GLOBAL      = 'global';       // Afeta todo o sistema
    case PER_TENANT  = 'per_tenant';   // Específico por tenant
    case PER_USER    = 'per_user';     // Específico por usuário
    case PERCENTAGE  = 'percentage';   // Rollout gradual por porcentagem
    case DATE_RANGE  = 'date_range';   // Ativo durante um período específico
    case ENVIRONMENT = 'environment';  // Específico por ambiente (dev, staging, prod)
    case AB_TEST     = 'ab_test';      // Para testes A/B ou multivariantes

    /**
     * Verifica se o tipo de feature flag requer um escopo específico
     */
    public function requiresScope(): bool
    {
        return match($this) {
            self::GLOBAL, self::ENVIRONMENT => false,
            default => true,
        };
    }

    /**
     * Retorna o nome amigável do tipo de feature flag
     */
    public function getName(): string
    {
        return match($this) {
            self::GLOBAL      => 'Global',
            self::PER_TENANT  => 'Por Tenant',
            self::PER_USER    => 'Por Usuário',
            self::PERCENTAGE  => 'Porcentagem',
            self::DATE_RANGE  => 'Período',
            self::ENVIRONMENT => 'Ambiente',
            self::AB_TEST     => 'Teste A/B',
        };
    }

    /**
     * Retorna a descrição do tipo de feature flag
     */
    public function getDescription(): string
    {
        return match($this) {
            self::GLOBAL      => 'Afeta todo o sistema independentemente de usuário ou tenant',
            self::PER_TENANT  => 'Pode ser ativado/desativado para tenants específicos',
            self::PER_USER    => 'Pode ser ativado/desativado para usuários específicos',
            self::PERCENTAGE  => 'Ativado para uma porcentagem específica de usuários/tenants',
            self::DATE_RANGE  => 'Ativo apenas durante um período específico',
            self::ENVIRONMENT => 'Ativo apenas em ambientes específicos (dev, staging, prod)',
            self::AB_TEST     => 'Usado para testes A/B ou multivariantes',
        };
    }
}
