<?php

namespace App\Core;

use App\Controllers\ErrorController;

class Router
{
    private $routes = [];
    private $errorController;

    public function __construct()
    {
        $this->errorController = new ErrorController();
    }

    public function add(string $method, string $uri, array $action): void
    {
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch(string $uri, string $method): void
    {
        $route = $this->findRoute($uri, $method);

        if (!$route) {
            $this->errorController->show(404, "Not Found");
            return;
        }

        [$controllerClass, $controllerMethod] = $route['action'];
        $params = $route['params'];

        // Simple dependency injection
        $dependencies = $this->getDependencies($controllerClass);
        $controller = new $controllerClass(...$dependencies);

        call_user_func_array([$controller, $controllerMethod], $params);
    }

    private function findRoute(string $uri, string $method): ?array
    {
        foreach ($this->routes[$method] ?? [] as $routeUri => $action) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $routeUri);
            if (preg_match("#^$pattern$#", $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['action' => $action, 'params' => $params];
            }
        }
        return null;
    }

    private function getDependencies(string $controllerClass): array
    {
        $reflection = new \ReflectionClass($controllerClass);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return [];
        }

        $params = $constructor->getParameters();
        $dependencies = [];
        foreach ($params as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = new ($type->getName())();
            }
        }
        return $dependencies;
    }
}