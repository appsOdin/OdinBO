<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application bootstrapper.
 */
final class App
{
    public function run(): void
    {
        $router = new Router();
        $routes = require __DIR__ . '/../../routes/web.php';

        foreach ($routes as $route) {
            $router->add(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middleware'] ?? []
            );
        }

        $router->dispatch(new Request());
    }
}
