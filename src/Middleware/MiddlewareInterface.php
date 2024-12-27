<?php

declare(strict_types=1);

namespace RapidRest\Middleware;

use RapidRest\Http\Request;
use RapidRest\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response;
}
