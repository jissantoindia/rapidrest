<?php

use RapidRest\Http\Request;
use RapidRest\Http\Response;
use RapidRest\Controllers\AuthController;
use RapidRest\Controllers\UserController;

// Auth routes
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->post('/api/auth/logout', [AuthController::class, 'logout']);
$app->get('/api/auth/me', [AuthController::class, 'me']);

// User routes
$app->get('/api/users', [UserController::class, 'index']);
$app->get('/api/users/{id}', [UserController::class, 'show']);
$app->post('/api/users', [UserController::class, 'store']);
$app->put('/api/users/{id}', [UserController::class, 'update']);
$app->delete('/api/users/{id}', [UserController::class, 'destroy']);
