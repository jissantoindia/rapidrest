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

    public function get(string $pattern, callable $handler): self
    {
        $this->router->get($pattern, $handler);
        return $this;
    }

    public function post(string $pattern, callable $handler): self
    {
        $this->router->post($pattern, $handler);
        return $this;
    }

    public function put(string $pattern, callable $handler): self
    {
        $this->router->put($pattern, $handler);
        return $this;
    }

    public function delete(string $pattern, callable $handler): self
    {
        $this->router->delete($pattern, $handler);
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

        $response->send();
    }
}
