<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Throttle;

use Max\Di\Context;
use Max\Http\Message\Response;
use Max\Http\Throttle\Handlers\CounterFixed;
use Max\Http\Throttle\Handlers\CounterSlider;
use Max\Http\Throttle\Handlers\ThrottleAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

class ThrottleMiddleware implements MiddlewareInterface
{
    /**
     * 默认配置参数.
     */
    public array $config = [
        'prefix'                       => 'throttle_',                    // 缓存键前缀，防止键与其他应用冲突
        'key'                          => true,                           // 节流规则 true为自动规则
        'visit_method'                 => ['GET', 'HEAD'],          // 要被限制的请求类型
        'visit_rate'                   => '10/m',                       // 节流频率 null 表示不限制 eg: 10/m  20/h  300/d
        'visit_enable_show_rate_limit' => true,     // 在响应体中设置速率限制的头部信息
        'visit_fail_code'              => 429,                   // 访问受限时返回的http状态码，当没有visit_fail_response时生效
        'visit_fail_text'              => 'Too Many Requests',   // 访问受限时访问的文本信息，当没有visit_fail_response时生效
        'visit_fail_response'          => null,              // 访问受限时的响应信息闭包回调
        'driver_name'                  => CounterFixed::class,       // 限流算法驱动
    ];

    public static array $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

    protected ThrottleAbstract $handler;

    protected $key;          // 解析后的标识

    protected int $waitSeconds = 0;             // 下次合法请求还有多少秒

    protected int $now = 0;             // 当前时间戳

    protected int $max_requests = 0;             // 规定时间内允许的最大请求次数

    protected int $expire = 0;             // 规定时间

    protected int $remaining = 0;             // 规定时间内还能请求的次数

    protected string $driver = CounterSlider::class;

    public function __construct(
        protected CacheInterface $cache
    ) {
        $this->handler = Context::getContainer()->make($this->driver);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->allowRequest($request)) {
            // 访问受限
            return $this->buildLimitException($this->waitSeconds, $request);
        }
        $response = $handler->handle($request);
        if (200 <= $response->getStatusCode() && 300 > $response->getStatusCode() && $this->config['visit_enable_show_rate_limit']) {
            // 将速率限制 headers 添加到响应中
            $response = $response->withHeader('X-Rate-Limit-Limit', $this->max_requests)
                ->withHeader('X-Rate-Limit-Remaining', $this->remaining < 0 ? 0 : $this->remaining)
                ->withHeader('X-Rate-Limit-Reset', $this->now + $this->expire);
        }
        return $response;
    }

    /**
     * 获取速率限制头.
     */
    public function getRateLimitHeaders(): array
    {
        return [
        ];
    }

    /**
     * 构建 Response Exception.
     */
    public function buildLimitException(int $waitSeconds, ServerRequestInterface $request): ResponseInterface
    {
        $visitFail = $this->config['visit_fail_response'] ?? null;
        if ($visitFail instanceof \Closure) {
            $response = Container::getInstance()->invokeFunction($visitFail, [$this, $request, $waitSeconds]);
            if (! $response instanceof ResponseInterface) {
                throw new \TypeError(sprintf('The closure must return %s instance', Response::class));
            }
        } else {
            $content  = str_replace('__WAIT__', (string) $waitSeconds, $this->config['visit_fail_text']);
            $response = \App\Http\Response::HTML($content, $this->config['visit_fail_code']);
        }
        if ($this->config['visit_enable_show_rate_limit']) {
            $response->withHeader('Retry-After', $waitSeconds);
        }
        return $response;
    }

    /**
     * 请求是否允许.
     */
    protected function allowRequest(ServerRequestInterface $request): bool
    {
        // 若请求类型不在限制内
        if (! in_array($request->getMethod(), $this->config['visit_method'])) {
            return true;
        }

        $key = $this->getCacheKey($request);
        if ($key === null) {
            return true;
        }
        [$max_requests, $duration] = $this->parseRate($this->config['visit_rate']);

        $micronow = microtime(true);
        $now      = (int) $micronow;

        $allow = $this->handler->allowRequest($key, $micronow, $max_requests, $duration, $this->cache);

        if ($allow) {
            // 允许访问
            $this->now          = $now;
            $this->expire       = $duration;
            $this->max_requests = $max_requests;
            $this->remaining    = $max_requests - $this->handler->getCurRequests();
            return true;
        }

        $this->waitSeconds = $this->handler->getWaitSeconds();
        return false;
    }

    /**
     * 生成缓存的 key.
     */
    protected function getCacheKey(ServerRequestInterface $request): ?string
    {
        $key = $this->config['key'];
        return serialize([
            'path' => $request->getUri()->getPath(),
            'ip'   => $request->getUri()->getPath(),
        ]);

        if ($key === null || $key === false || $this->config['visit_rate'] === null) {
            // 关闭当前限制
            return null;
        }

        if ($key === true) {
            $key = $request->getRealIp();
        } elseif (str_contains($key, '__')) {
            $key = str_replace(['__CONTROLLER__', '__ACTION__', '__IP__'], [$request->controller(), $request->action(), $request->getRealIp()], $key);
        }

        return md5($this->config['prefix'] . $key . $this->config['driver_name']);
    }

    protected function getRealIp(ServerRequestInterface $request)
    {
        $headers = $request->getHeaders();
        if (isset($headers['x-forwarded-for'][0]) && ! empty($headers['x-forwarded-for'][0])) {
            return $headers['x-forwarded-for'][0];
        }
        if (isset($headers['x-real-ip'][0]) && ! empty($headers['x-real-ip'][0])) {
            return $headers['x-real-ip'][0];
        }
        $serverParams = $request->getServerParams();

        return $serverParams['remote_addr'] ?? '';
    }

    /**
     * 解析频率配置项.
     *
     * @return int[]
     */
    protected function parseRate(string $rate): array
    {
        [$num, $period] = explode('/', $rate);
        $max_requests   = (int) $num;
        $duration       = static::$duration[$period] ?? (int) $period;
        return [$max_requests, $duration];
    }
}
