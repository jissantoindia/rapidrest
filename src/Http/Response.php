<?php

declare(strict_types=1);

namespace RapidRest\Http;

class Response
{
    private int $statusCode;
    private array $headers;
    private mixed $body;

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        mixed $body = null
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function withJson($data, int $statusCode = null): self
    {
        $new = clone $this;
        $new->headers['Content-Type'] = 'application/json';
        $new->body = json_encode($data, JSON_THROW_ON_ERROR);
        if ($statusCode !== null) {
            $new->statusCode = $statusCode;
        }
        return $new;
    }

    public function withStatus(int $statusCode): self
    {
        $new = clone $this;
        $new->statusCode = $statusCode;
        return $new;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->body !== null) {
            echo $this->body;
        }
    }
}
