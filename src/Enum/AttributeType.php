<?php

declare(strict_types=1);

namespace App\Enum;

enum AttributeType: string
{
    case String = 'string';
    case Text = 'text';
    case Numeric = 'numeric';
    case Boolean = 'boolean';
    case Date = 'date';
    case Period = 'period';
    case Choice = 'choice';
    case Image = 'image';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }
}
