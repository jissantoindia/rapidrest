<?php

declare(strict_types=1);

namespace RapidRest\Controllers;

use RapidRest\Http\Controller;
use RapidRest\Http\Request;
use RapidRest\Http\Response;
use RapidRest\Models\User;

class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $data = $request->getParsedBody();

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $errors = $this->validate($data, $rules);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Find user by email
        $connection = app()->get('db');
        $user = User::query()
            ->where('email', '=', $data['email'])
            ->first();

        if (!$user || !$user->verifyPassword($data['password'])) {
            return $this->error('Invalid credentials', 401);
        }

        // Generate JWT token
        $token = $this->generateToken($user);

        return $this->success([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function register(Request $request): Response
    {
        $data = $request->getParsedBody();

        $rules = [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];

        $errors = $this->validate($data, $rules);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Check if email already exists
        $exists = User::query()
            ->where('email', '=', $data['email'])
            ->exists();

        if ($exists) {
            return $this->error('Email already registered', 422);
        }

        $user = new User($data);
        $user->setPassword($data['password']);
        $user->save();

        // Generate JWT token
        $token = $this->generateToken($user);

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], 'Registration successful', 201);
    }

    private function generateToken(User $user): string
    {
        // Implement JWT token generation
        // This is a placeholder - you should use a proper JWT library
        $header = base64_encode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));

        $payload = base64_encode(json_encode([
            'sub' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]));

        $signature = hash_hmac(
            'sha256',
            "$header.$payload",
            $_ENV['JWT_SECRET'] ?? 'your-secret-key'
        );

        return "$header.$payload.$signature";
    }
}
