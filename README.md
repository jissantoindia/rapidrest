# RapidRest PHP Framework

A high-performance, developer-friendly PHP library for building RESTful APIs, inspired by modern frameworks like FastAPI and Next.js.

## Features

- Modern PHP 8.1+ with strict typing
- Intuitive routing system with support for path parameters
- Middleware support for request/response processing
- Built-in request validation and parsing
- Database migrations with CLI support
- JSON response handling
- PSR-7 compliant HTTP message interfaces
- Clean and maintainable codebase
- CLI tools for common tasks
- Database query builder
- Model relationships
- JWT authentication support

## Installation

```bash
# Create a new project
composer create-project jissantoindia/rapidrest my-api

# Or add to existing project
composer require jissantoindia/rapidrest
```

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use RapidRest\Application;
use RapidRest\Http\Request;
use RapidRest\Http\Response;

$app = new Application();

// Add a route with a path parameter
$app->get('/hello/{name}', function (Request $request, string $name) {
    return (new Response())
        ->withJson([
            'message' => "Hello, $name!"
        ]);
});

// Run the application
$app->run();
```

## Documentation

### CLI Commands

RapidRest comes with a powerful CLI tool for managing your application:

```bash
# Create a new migration
./rapid make:migration create_users_table

# Run migrations
./rapid migrate

# Rollback migrations
./rapid migrate:rollback

# Refresh database (rollback all and migrate)
./rapid migrate:refresh

# Show migration status
./rapid migrate:status
```

### Database Migrations

Create and manage your database schema using migrations:

```php
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### Models

Define models with relationships:

```php
class User extends Model
{
    protected static string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
    ];

    public function posts(): array
    {
        return $this->hasMany(Post::class);
    }

    public function profile(): Profile
    {
        return $this->hasOne(Profile::class);
    }
}
```

### Query Builder

Fluent query building:

```php
// Basic queries
$users = User::query()
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->get();

// Complex queries
$users = User::query()
    ->select(['users.*', 'profiles.bio'])
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.is_active', '=', true)
    ->whereIn('users.role', ['admin', 'moderator'])
    ->limit(10)
    ->get();
```

### Routing

The routing system supports common HTTP methods and path parameters:

```php
// GET request
$app->get('/users', function (Request $request) {
    return (new Response())->withJson(['users' => []]);
});

// POST request with JSON body
$app->post('/users', function (Request $request) {
    $data = $request->getParsedBody();
    return (new Response())
        ->withJson(['message' => 'User created'], 201);
});

// Path parameters
$app->get('/users/{id}', function (Request $request, string $id) {
    return (new Response())->withJson(['id' => $id]);
});
```

### Middleware

Add middleware to process requests/responses:

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $token = $request->getHeader('Authorization');
        if (!$token) {
            return new Response(401, [], ['error' => 'Unauthorized']);
        }
        
        // Verify JWT token
        try {
            $user = $this->jwt->verify($token);
            $request = $request->withAttribute('user', $user);
            return $handler($request);
        } catch (Exception $e) {
            return new Response(401, [], ['error' => 'Invalid token']);
        }
    }
}

$app->use(new AuthMiddleware());
```

### Validation

Validate incoming requests:

```php
$rules = [
    'name' => 'required|min:2|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8',
    'role' => 'in:admin,user,editor',
];

$errors = $this->validate($data, $rules);
if (!empty($errors)) {
    return $this->error($errors, 422);
}
```

## Configuration

Copy the `.env.example` file to `.env` and update the settings:

```env
DB_HOST=localhost
DB_DATABASE=rapidrest
DB_USERNAME=root
DB_PASSWORD=

APP_ENV=development
APP_DEBUG=true
APP_KEY=your-secret-key

JWT_SECRET=your-jwt-secret
JWT_TTL=3600
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
