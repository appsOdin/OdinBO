<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller.
 */
abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $statusCode = 200): void
    {
        Response::json($payload, $statusCode);
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }
}
