<?php
/**
 * Router Class
 * Simple RESTful router without external dependencies
 */

class Router {
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '';

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, callable $handler): self {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): self {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): self {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, callable $handler): self {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->basePath . $path,
            'handler' => $handler
        ];
        
        return $this;
    }

    public function middleware(callable $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        foreach ($this->middleware as $middleware) {
            $middleware();
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $params = $this->matchRoute($route['path'], $uri);
            
            if ($params !== false) {
                $this->handleCORS();
                
                try {
                    $result = call_user_func_array($route['handler'], $params);
                    
                    if ($result !== null && !is_array($result)) {
                        echo $result;
                    }
                } catch (Exception $e) {
                    $this->handleException($e);
                }
                
                return;
            }
        }
        
        $this->handleNotFound();
    }

    private function matchRoute(string $routePath, string $uri): array|false {
        $routePath = rtrim($routePath, '/');
        $uri = rtrim($uri, '/');
        
        if ($routePath === $uri) {
            return [];
        }
        
        $routeParts = explode('/', $routePath);
        $uriParts = explode('/', $uri);
        
        if (count($routeParts) !== count($uriParts)) {
            return false;
        }
        
        $params = [];
        
        foreach ($routeParts as $index => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $paramName = trim($part, '{}');
                $params[$paramName] = $uriParts[$index];
            } elseif ($part !== $uriParts[$index]) {
                return false;
            }
        }
        
        return $params;
    }

    private function handleCORS(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json');
    }

    private function handleNotFound(): void {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found',
            'data' => null,
            'timestamp' => date('c')
        ]);
    }

    private function handleException(Exception $e): void {
        $statusCode = 500;
        
        if ($e instanceof AppException) {
            $statusCode = $e->getStatusCode();
        }
        
        http_response_code($statusCode);
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => $e instanceof ValidationException ? ['errors' => $e->getErrors()] : null,
            'timestamp' => date('c')
        ]);
    }
}

function route(string $path, callable $handler): Router {
    global $router;
    return $router->get($path, $handler);
}
