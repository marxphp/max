<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\JWT\Contract;

interface Authenticatable
{
    public function getIdentifier();

    public function getClaims(): array;
}
