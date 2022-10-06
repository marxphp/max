<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\JsonRpc\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RpcService
{
    public function __construct(
        public string $name
    ) {
    }
}
