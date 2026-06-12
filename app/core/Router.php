<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal MVC router.
 */
final class Router
{
    /**
     * @var array<int, array{method: string, path: string, handler: callable|array{0:string,1:string}, middleware: array<int, string>}>
     */
    private array $routes = [];

    /**
     * @param callable|array{0: string, 1: string} $handler
     * @param array<int, string> $middleware
     */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $requestMethod = $request->method();
        $requestPath = rtrim($request->path(), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod || $route['path'] !== $requestPath) {
                continue;
            }

            foreach ($route['middleware'] as $middlewareClass) {
                if (!class_exists($middlewareClass)) {
                    throw new \RuntimeException('Middleware not found: ' . $middlewareClass);
                }

                $middleware = new $middlewareClass();
                $middleware->handle($request);
            }

            $handler = $route['handler'];
            if (is_callable($handler)) {
                $handler($request);
                return;
            }

            [$controllerClass, $method] = $handler;
            if (!class_exists($controllerClass)) {
                throw new \RuntimeException('Controller not found: ' . $controllerClass);
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException('Method not found: ' . $controllerClass . '::' . $method);
            }

            $controller->{$method}($request);
            return;
        }

        http_response_code(404);
        echo '404 - Page not found';
    }
}
