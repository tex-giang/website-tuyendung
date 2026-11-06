<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router tối giản: hỗ trợ tham số {slug}, middleware và 404 tùy biến.
 * Thứ tự khai báo route quyết định thứ tự match — route cụ thể đặt trước route động.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array, middleware:array}> */
    private array $routes = [];

    /** @var callable|null */
    private $notFound = null;

    public function setNotFound(callable $handler): void
    {
        $this->notFound = $handler;
    }

    public function get(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $this->compile($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware) {
                $middleware();
            }

            $this->call($route['handler'], array_values($params));
            return;
        }

        $this->abort(404);
    }

    private function compile(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $normalized
        );

        return '#^' . $pattern . '/?$#';
    }

    private function call(callable|array|string $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->{$action}(...$params);
            return;
        }
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $action] = explode('@', $handler, 2);
            $class = 'App\\Controllers\\' . $class;
            (new $class())->{$action}(...$params);
            return;
        }
        $handler(...$params);
    }

    private function abort(int $code): void
    {
        if ($this->notFound !== null) {
            ($this->notFound)($code);
            return;
        }
        http_response_code($code);
        echo $code === 404 ? 'Không tìm thấy trang.' : 'Lỗi hệ thống.';
    }
}
