<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static array $groupStack = [];

    public static function get(string $uri, callable|array|string $action, array $middleware = []): void
    {
        self::addRoute('GET', $uri, $action, $middleware);
    }

    public static function post(string $uri, callable|array|string $action, array $middleware = []): void
    {
        self::addRoute('POST', $uri, $action, $middleware);
    }

    public static function put(string $uri, callable|array|string $action, array $middleware = []): void
    {
        self::addRoute('PUT', $uri, $action, $middleware);
    }

    public static function delete(string $uri, callable|array|string $action, array $middleware = []): void
    {
        self::addRoute('DELETE', $uri, $action, $middleware);
    }

    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    private static function addRoute(string $method, string $uri, callable|array|string $action, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, (array)$group['middleware']);
            }
        }

        $fullUri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        if ($fullUri === '//') {
            $fullUri = '/';
        }

        $allMiddleware = array_merge($groupMiddleware, $middleware);

        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullUri);
        $pattern = '#^' . $pattern . '$#';

        self::$routes[] = [
            'method' => strtoupper($method),
            'uri' => $fullUri,
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $allMiddleware,
        ];
    }

    public static function dispatch(string $uri, string $method): void
    {
        // Strip query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = '/' . trim(rawurldecode($uri), '/');
        if ($uri === '//') {
            $uri = '/';
        }

        // Support _method override for forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Run middleware pipeline
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw) && class_exists($mw)) {
                        $instance = new $mw();
                        $result = $instance->handle();
                        if ($result === false) {
                            return; // Middleware aborted request
                        }
                    } elseif (is_callable($mw)) {
                        $result = $mw();
                        if ($result === false) {
                            return;
                        }
                    }
                }

                // Execute action
                $action = $route['action'];
                if (is_callable($action)) {
                    call_user_func_array($action, $params);
                    return;
                }

                if (is_array($action)) {
                    [$controllerClass, $methodName] = $action;
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $methodName], $params);
                    return;
                }

                if (is_string($action) && str_contains($action, '@')) {
                    [$controllerClass, $methodName] = explode('@', $action, 2);
                    $controller = new $controllerClass();
                    call_user_func_array([$controller, $methodName], $params);
                    return;
                }
            }
        }

        // Route Not Found (404)
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json') || str_starts_with($uri, '/api/')) {
            Response::json(['error' => 'Endpoint tidak ditemukan', 'path' => $uri], 404);
            return;
        }

        http_response_code(404);
        $errorView = dirname(__DIR__, 2) . '/resources/views/errors/404.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>404 - Halaman Tidak Ditemukan</h1><p>Halaman yang Anda tuju tidak tersedia.</p>";
        }
    }
}
