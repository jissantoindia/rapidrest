<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RapidRest\Application;
use RapidRest\Database\ConnectionPool;
use RapidRest\Middleware\CorsMiddleware;
use RapidRest\Middleware\LoggingMiddleware;
use RapidRest\Controllers\UserController;
use RapidRest\Controllers\AuthController;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup database connection pool
$dbConfig = [
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
];
$pool = new ConnectionPool($dbConfig);

// Setup logger
$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(__DIR__ . '/app.log', Logger::DEBUG));

// Create application
$app = new Application();

// Register services
$app->singleton(ConnectionPool::class, fn() => $pool);
$app->singleton(Logger::class, fn() => $logger);

// Add middleware
$app->use(new CorsMiddleware([
    'allowedOrigins' => ['http://localhost:8000'],
]));
$app->use(new LoggingMiddleware($logger));

// User routes
$userController = new UserController();
$app->get('/users', [$userController, 'index']);
$app->get('/users/{id}', [$userController, 'show']);
$app->post('/users', [$userController, 'store']);
$app->put('/users/{id}', [$userController, 'update']);
$app->delete('/users/{id}', [$userController, 'destroy']);

// Auth routes
$authController = new AuthController();
$app->post('/auth/login', [$authController, 'login']);
$app->post('/auth/register', [$authController, 'register']);

// Run the application
$app->run();
