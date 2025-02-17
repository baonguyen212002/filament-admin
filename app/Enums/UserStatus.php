<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
final class UserStatus extends Enum
{
    const WAITING = 0;
    const ACTIVATED = 1;
    const DISABLE = 2;
}
