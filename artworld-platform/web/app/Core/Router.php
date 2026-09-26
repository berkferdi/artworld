<?php

declare(strict_types=1);

namespace Web\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array{0:class-string,1:string}, defaults:array<string,string>}> */
    private array $routes = [];

    /** @param array<string, string> $defaults */
    public function get(string $pattern, callable|array $handler, array $defaults = []): void
    {
        $this->add('GET', $pattern, $handler, $defaults);
    }

    /** @param array<string, string> $defaults */
    public function post(string $pattern, callable|array $handler, array $defaults = []): void
    {
        $this->add('POST', $pattern, $handler, $defaults);
    }

    /** @param array<string, string> $defaults */
    private function add(string $method, string $pattern, callable|array $handler, array $defaults = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'defaults' => $defaults,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/') ?: '/';
        }
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#u';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = $route['defaults'];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $ref = new \ReflectionMethod($controller, $action);
                $args = [];
                foreach ($ref->getParameters() as $param) {
                    $name = $param->getName();
                    if (array_key_exists($name, $params)) {
                        $args[] = $params[$name];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        $args[] = null;
                    }
                }
                $controller->{$action}(...$args);
                return;
            }
            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', [
            'title' => 'Sayfa bulunamadı',
            'metaDescription' => 'İstenen sayfa bulunamadı.',
        ]);
    }
}
