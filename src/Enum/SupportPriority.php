<?php

declare(strict_types=1);

namespace App\Enum;

enum SupportPriority: string
{
    case High = 'High';
    case Average = 'Average';
    case Low = 'Low';
}
