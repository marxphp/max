<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Server;

use InvalidArgumentException;
use Max\Http\Server\Contract\RouteResolverInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected RouteResolverInterface $routeResolver,
        protected array $middlewares = [],
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($middlewareClass = array_shift($this->middlewares)) {
            $middleware = $this->container->make($middlewareClass);
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware->process($request, $this);
            }
            throw new InvalidArgumentException(sprintf('The middleware %s should implement Psr\Http\Server\MiddlewareInterface', $middlewareClass));
        }
        return $this->routeResolver->resolve($request);
    }

    /**
     * 添加中间件.
     */
    public function use(string ...$middleware): static
    {
        array_push($this->middlewares, ...$middleware);
        return $this;
    }
}
