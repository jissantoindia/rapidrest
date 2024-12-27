<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RapidRest\Application;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create the application
$app = new Application();

// Load routes from routes directory
$routesDir = __DIR__ . '/../routes';
foreach (glob($routesDir . '/*.php') as $routeFile) {
    require $routeFile;
}

// Run the application
$app->run();
