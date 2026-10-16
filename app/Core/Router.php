<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    private function add(string $method, string $path, array $action, array $middleware): void
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', '/' . trim($path, '/'));
        $this->routes[] = compact('method', 'action', 'middleware') + ['pattern' => '#^' . $pattern . '$#'];
    }

    public function dispatch(Request $request): Response
    {
        $allowed = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $allowed = true;
                continue;
            }

            if ($request->method === 'POST' && !Csrf::verify($request)) {
                throw new HttpException(419, __('Your session has expired, please reload the page.'));
            }

            foreach ($route['middleware'] as $name) {
                if ($response = Middleware::handle($name, $request)) {
                    return $response;
                }
            }

            [$class, $method] = $route['action'];
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return (new $class())->{$method}($request, ...$params);
        }

        throw new HttpException($allowed ? 405 : 404);
    }
}
