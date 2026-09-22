<?php

declare(strict_types=1);

namespace App\Service;

final class AttributeValueHelper
{
    public static function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (\is_array($value)) {
            if ($value === []) {
                return true;
            }

            if (\array_key_exists('from', $value) || \array_key_exists('to', $value)) {
                return ($value['from'] ?? '') === '' && ($value['to'] ?? '') === '';
            }
        }

        return false;
    }
}
