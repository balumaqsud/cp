<?php

declare(strict_types=1);

namespace App\Enum;

enum AccessOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            '=' => self::Eq->value,
            '!=' => self::Neq->value,
            '>' => self::Gt->value,
            '>=' => self::Gte->value,
            '<' => self::Lt->value,
            '<=' => self::Lte->value,
        ];
    }
}
