<?php

declare(strict_types=1);

/**
 * This file is part of the Max package.
 *
 * (c) Cheng Yao <987861463@qq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Max\Di\Aop\Traits;

use Closure;
use Max\Di\Aop\JoinPoint;
use Max\Di\AspectManager;
use Max\Di\Context;
use Max\Di\Exceptions\NotFoundException;
use Max\Utils\Pipeline;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

trait ProxyHandler
{
    /**
     * @param string   $function
     * @param Closure $callback
     * @param array    $arguments
     *
     * @return mixed
     * @throws ReflectionException
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     */
    protected function __callViaProxy(string $function, Closure $callback, array $arguments): mixed
    {
        $joinPoint = new JoinPoint($this, $function, $arguments, $callback);
        if (empty($aspects = AspectManager::getMethodAspects(__CLASS__, $function))) {
            return $joinPoint->process();
        }
        return (new Pipeline(Context::getContainer()))
            ->send($joinPoint)
            ->through($aspects)
            ->via('process')
            ->then(function(JoinPoint $joinPoint) {
                return $joinPoint->process();
            });
    }
}