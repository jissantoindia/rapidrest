<?php

declare(strict_types=1);

namespace RapidRest\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Request
{
    private array $queryParams;
    private array $parsedBody;
    private array $headers;
    private string $method;
    private UriInterface $uri;

    public function __construct(ServerRequestInterface $request)
    {
        $this->queryParams = $request->getQueryParams();
        $this->parsedBody = $request->getParsedBody() ?? [];
        $this->headers = $request->getHeaders();
        $this->method = $request->getMethod();
        $this->uri = $request->getUri();
    }

    public static function fromGlobals(): self
    {
        // Create PSR-7 ServerRequest from globals
        // This is a simplified version - in production, use a proper PSR-7 implementation
        $uri = new Uri(
            isset($_SERVER['HTTPS']) ? 'https' : 'http',
            $_SERVER['HTTP_HOST'] ?? 'localhost',
            $_SERVER['REQUEST_URI'] ?? '/'
        );

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[substr($key, 5)] = $value;
            }
        }

        $request = new ServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri,
            $headers,
            $_GET,
            $_POST,
            $_FILES
        );

        return new self($request);
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getQueryParam(string $name, $default = null)
    {
        return $this->queryParams[$name] ?? $default;
    }

    public function getBodyParam(string $name, $default = null)
    {
        return $this->parsedBody[$name] ?? $default;
    }
}
