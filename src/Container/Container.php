<?php

declare(strict_types=1);

namespace RapidRest\Container;

use Psr\Container\ContainerInterface;
use RuntimeException;

class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];

    public function get(string $id)
    {
        if ($this->has($id)) {
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }

            $concrete = $this->bindings[$id];
            return $concrete instanceof \Closure ? $concrete($this) : $concrete;
        }

        throw new RuntimeException("No binding found for $id");
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, function ($container) use ($concrete, $abstract) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete instanceof \Closure
                    ? $concrete($container)
                    : $concrete;
            }
            return $this->instances[$abstract];
        });
    }

    public function make(string $abstract, array $parameters = [])
    {
        return $this->get($abstract);
    }
}
