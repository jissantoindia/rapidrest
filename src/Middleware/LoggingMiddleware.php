<?php

declare(strict_types=1);

namespace RapidRest\Middleware;

use Monolog\Logger;
use RapidRest\Http\Request;
use RapidRest\Http\Response;

class LoggingMiddleware implements MiddlewareInterface
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function process(Request $request, callable $handler): Response
    {
        $startTime = microtime(true);

        // Log request
        $this->logger->info('Incoming request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
        ]);

        try {
            $response = $handler($request);

            // Log response
            $duration = microtime(true) - $startTime;
            $this->logger->info('Response sent', [
                'status' => $response->getStatusCode(),
                'duration' => round($duration * 1000, 2) . 'ms',
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('Request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
