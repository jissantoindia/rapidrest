<?php

declare(strict_types=1);

namespace RapidRest\Middleware;

use RapidRest\Http\Request;
use RapidRest\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'Authorization'],
            'exposedHeaders' => [],
            'maxAge' => 86400,
            'supportsCredentials' => false,
        ], $options);
    }

    public function process(Request $request, callable $handler): Response
    {
        $response = $handler($request);

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response(204);
        }

        // Add CORS headers
        $origin = $request->getHeader('Origin');
        if ($origin && $this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        if ($this->options['supportsCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->options['exposedHeaders'])) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->options['exposedHeaders'])
            );
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                implode(', ', $this->options['allowedMethods'])
            );

            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                implode(', ', $this->options['allowedHeaders'])
            );

            $response = $response->withHeader(
                'Access-Control-Max-Age',
                (string) $this->options['maxAge']
            );
        }

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->options['allowedOrigins'])) {
            return true;
        }

        return in_array($origin, $this->options['allowedOrigins']);
    }
}
