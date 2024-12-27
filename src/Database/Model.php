<?php

declare(strict_types=1);

namespace RapidRest\Database;

abstract class Model
{
    protected static string $table;
    protected array $attributes = [];
    protected array $original = [];
    protected array $fillable = [];
    protected array $hidden = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function toArray(): array
    {
        return array_diff_key($this->attributes, array_flip($this->hidden));
    }

    public static function getTable(): string
    {
        return static::$table;
    }

    public static function query(): QueryBuilder
    {
        return (new QueryBuilder())->table(static::getTable());
    }

    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function save(): bool
    {
        $query = static::query();
        $connection = app()->get(ConnectionPool::class)->getConnection();

        try {
            if (isset($this->attributes['id'])) {
                $result = $query->update($this->attributes);
            } else {
                $result = $query->insert($this->attributes);
            }

            $stmt = $connection->prepare($result['sql']);
            $success = $stmt->execute($result['params']);

            if ($success && !isset($this->attributes['id'])) {
                $this->attributes['id'] = $connection->lastInsertId();
            }

            return $success;
        } finally {
            app()->get(ConnectionPool::class)->releaseConnection($connection);
        }
    }

    public static function find($id)
    {
        $connection = app()->get(ConnectionPool::class)->getConnection();

        try {
            $query = static::query()->where('id', '=', $id);
            $result = $query->toSql();
            
            $stmt = $connection->prepare($result['sql']);
            $stmt->execute($result['params']);
            
            $data = $stmt->fetch();
            return $data ? new static($data) : null;
        } finally {
            app()->get(ConnectionPool::class)->releaseConnection($connection);
        }
    }

    public static function all(): array
    {
        $connection = app()->get(ConnectionPool::class)->getConnection();

        try {
            $query = static::query();
            $result = $query->toSql();
            
            $stmt = $connection->prepare($result['sql']);
            $stmt->execute($result['params']);
            
            $models = [];
            while ($data = $stmt->fetch()) {
                $models[] = new static($data);
            }
            return $models;
        } finally {
            app()->get(ConnectionPool::class)->releaseConnection($connection);
        }
    }
}
