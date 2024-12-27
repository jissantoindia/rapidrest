<?php

declare(strict_types=1);

namespace RapidRest;

use RapidRest\Http\Request;
use RapidRest\Http\Response;
use RapidRest\Routing\Router;
use RapidRest\Middleware\MiddlewareInterface;
use RapidRest\Container\Container;

class Application
{
    private Router $router;
    private array $config;
    private Container $container;

    public function __construct(array $config = [])
    {
        $this->router = new Router();
        $this->config = $config;
        $this->container = new Container();
    }

    public function get(string $pattern, callable|array $handler): self
    {
        $this->router->get($pattern, $this->resolveHandler($handler));
        return $this;
    }

    public function post(string $pattern, callable|array $handler): self
    {
        $this->router->post($pattern, $this->resolveHandler($handler));
        return $this;
    }

    public function put(string $pattern, callable|array $handler): self
    {
        $this->router->put($pattern, $this->resolveHandler($handler));
        return $this;
    }

    public function delete(string $pattern, callable|array $handler): self
    {
        $this->router->delete($pattern, $this->resolveHandler($handler));
        return $this;
    }

    public function use(MiddlewareInterface $middleware): self
    {
        $this->router->addMiddleware($middleware);
        return $this;
    }

    public function bind(string $abstract, $concrete): self
    {
        $this->container->bind($abstract, $concrete);
        return $this;
    }

    public function singleton(string $abstract, $concrete): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }

    public function make(string $abstract, array $parameters = [])
    {
        return $this->container->make($abstract, $parameters);
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        
        try {
            $response = $this->router->dispatch($request);
        } catch (\Throwable $e) {
            $response = new Response(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        }

        $this->send($response);
    }

    private function send(Response $response): void
    {
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        echo $response->getBody();
    }

    private function resolveHandler(callable|array $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            
            if (!class_exists($class)) {
                throw new \RuntimeException("Controller class {$class} not found");
            }

            $controller = $this->container->get($class);
            
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method {$method} not found in controller {$class}");
            }

            return [$controller, $method];
        }

        throw new \InvalidArgumentException('Invalid route handler');
    }
}
