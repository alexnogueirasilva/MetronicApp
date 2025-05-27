<?php declare(strict_types = 1);

namespace App\Enums;

/**
 * @property string $value
 */
enum AuthorizationEnum: string
{
    case HOLDING_VIEW   = 'holding:view';
    case HOLDING_CREATE = 'holding:create';
    case HOLDING_UPDATE = 'holding:update';
    case HOLDING_DELETE = 'holding:delete';
}
