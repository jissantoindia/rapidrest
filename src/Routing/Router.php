<?php

declare(strict_types=1);

namespace RapidRest\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RapidRest\Http\Request;
use RapidRest\Http\Response;
use RapidRest\Middleware\MiddlewareInterface;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private ?Dispatcher $dispatcher = null;

    public function addRoute(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
        $this->dispatcher = null;
        return $this;
    }

    public function get(string $pattern, callable $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): self
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): self
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
                foreach ($this->routes as $route) {
                    $r->addRoute($route['method'], $route['pattern'], $route['handler']);
                }
            });
        }

        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response(404, [], ['error' => 'Not Found']);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response(405, [], ['error' => 'Method Not Allowed']);

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                // Apply middleware
                $next = function (Request $request) use ($handler, $vars) {
                    return $this->handleRequest($request, $handler, $vars);
                };

                foreach (array_reverse($this->middleware) as $middleware) {
                    $next = function (Request $request) use ($middleware, $next) {
                        return $middleware->process($request, $next);
                    };
                }

                return $next($request);
        }

        return new Response(500, [], ['error' => 'Internal Server Error']);
    }

    private function handleRequest(Request $request, callable $handler, array $vars): Response
    {
        try {
            $response = $handler($request, ...array_values($vars));

            if (!$response instanceof Response) {
                $response = new Response(200, [], $response);
            }

            return $response;
        } catch (\Throwable $e) {
            return new Response(
                500,
                [],
                [
                    'error' => 'Internal Server Error',
                    'message' => $e->getMessage(),
                    'trace' => $_ENV['APP_DEBUG'] ? $e->getTraceAsString() : null
                ]
            );
        }
    }
}
