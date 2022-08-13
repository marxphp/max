<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Server\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OnRequest
{
    public float $requestedAt;

    public function __construct(
        public ServerRequestInterface $request,
        public ?ResponseInterface $response = null
    ) {
        $this->requestedAt = microtime(true);
    }
}
