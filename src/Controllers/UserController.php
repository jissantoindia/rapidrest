<?php

declare(strict_types=1);

namespace RapidRest\Controllers;

use RapidRest\Http\Controller;
use RapidRest\Http\Request;
use RapidRest\Http\Response;
use RapidRest\Models\User;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return $this->success($users);
    }

    public function show(Request $request, string $id): Response
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }

        return $this->success($user);
    }

    public function store(Request $request): Response
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

        $user = new User($data);
        $user->setPassword($data['password']);
        $user->save();

        return $this->success($user, 'User created successfully', 201);
    }

    public function update(Request $request, string $id): Response
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }

        $data = $request->getParsedBody();

        $rules = [
            'name' => 'min:2',
            'email' => 'email',
            'password' => 'min:6',
        ];

        $errors = $this->validate($data, $rules);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        if (isset($data['password'])) {
            $user->setPassword($data['password']);
            unset($data['password']);
        }

        $user->fill($data);
        $user->save();

        return $this->success($user, 'User updated successfully');
    }

    public function destroy(Request $request, string $id): Response
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }

        // Implement soft delete or hard delete based on your requirements
        $query = User::query()->where('id', '=', $id)->delete();
        
        return $this->success(null, 'User deleted successfully');
    }
}
