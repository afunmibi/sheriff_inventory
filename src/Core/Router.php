<?php
namespace Core;

class Router {
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $prefix = '';

    public function group(string $prefix, array $middleware, callable $callback): void {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void {
        $fullPath = $this->prefix . $path;
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): mixed {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw)) {
                        $mw = new $mw();
                    }
                    if (is_callable($mw)) {
                        $result = $mw();
                        if ($result === false) return null;
                    }
                }

                // Extract named params
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                // Call handler
                if (is_array($route['handler'])) {
                    [$class, $method] = $route['handler'];
                    if (is_string($class)) {
                        $instance = new $class();
                    } else {
                        $instance = $class;
                    }
                    return call_user_func_array([$instance, $method], $params);
                }

                return call_user_func_array($route['handler'], $params);
            }
        }

        return null;
    }

    public function getRoutes(): array {
        return $this->routes;
    }
}
