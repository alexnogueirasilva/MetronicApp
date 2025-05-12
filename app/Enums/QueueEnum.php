<?php declare(strict_types = 1);

namespace App\Enums;

/**
 * Enum para categorizar filas de processamento
 *
 * Este enum categoriza os diferentes tipos de filas que o sistema utiliza,
 * facilitando a organização e monitoramento de jobs no Laravel Horizon.
 */
enum QueueEnum: string
{
    /**
     * Fila de alta prioridade para eventos críticos relacionados à autenticação
     * (ex: verificação de dispositivos, email OTP em duas etapas, etc)
     */
    case AUTH_CRITICAL = 'auth-critical';

    /**
     * Fila para tarefas de autenticação regulares
     * (ex: links mágicos, recuperação de senha, etc)
     */
    case AUTH_DEFAULT = 'auth-default';

    /**
     * Fila para notificações importantes que precisam ser enviadas rapidamente
     */
    case NOTIFICATIONS_HIGH = 'notifications-high';

    /**
     * Fila para notificações regulares que podem tolerar algum atraso
     */
    case NOTIFICATIONS_DEFAULT = 'notifications-default';

    /**
     * Fila para processamento em lote ou tarefas de fundo
     */
    case BACKGROUND = 'background';

    /**
     * Fila para tarefas de manutenção do sistema
     */
    case MAINTENANCE = 'maintenance';

    /**
     * Fila padrão para jobs não categorizados
     */
    case DEFAULT = 'default';
}
