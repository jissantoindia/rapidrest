<?php

declare(strict_types=1);

namespace RapidRest\Database\Schema;

class Schema
{
    private static ?SchemaManager $manager = null;

    public static function setManager(SchemaManager $manager): void
    {
        self::$manager = $manager;
    }

    public static function create(string $table, callable $callback): void
    {
        if (!self::$manager) {
            throw new \RuntimeException('SchemaManager not set');
        }
        self::$manager->createTable($table, $callback);
    }

    public static function dropIfExists(string $table): void
    {
        if (!self::$manager) {
            throw new \RuntimeException('SchemaManager not set');
        }
        self::$manager->dropTable($table);
    }

    public static function hasTable(string $table): bool
    {
        if (!self::$manager) {
            throw new \RuntimeException('SchemaManager not set');
        }
        return self::$manager->hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (!self::$manager) {
            throw new \RuntimeException('SchemaManager not set');
        }
        return self::$manager->hasColumn($table, $column);
    }
}
