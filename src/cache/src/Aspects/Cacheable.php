<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Cache\Aspects;

use Closure;
use Max\Aop\Contracts\AspectInterface;
use Max\Aop\JoinPoint;
use Psr\Container\ContainerExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionException;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Cacheable implements AspectInterface
{
    public function __construct(
        protected int $ttl = 0,
        protected string $prefix = '',
        protected ?string $key = null
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function process(JoinPoint $joinPoint, Closure $next): mixed
    {
        $cache = make(CacheInterface::class);
        return $cache->remember($this->getKey($joinPoint), fn () => $next($joinPoint), $this->ttl);
    }

    protected function getKey(JoinPoint $joinPoint): string
    {
        $key = $this->key ?? ($joinPoint->class . ':' . $joinPoint->method . ':' . serialize(array_filter($joinPoint->parameters->getArrayCopy(), fn ($item) => ! is_object($item))));
        return $this->prefix ? ($this->prefix . ':' . $key) : $key;
    }
}
