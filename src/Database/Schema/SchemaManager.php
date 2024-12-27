<?php

declare(strict_types=1);

namespace RapidRest\Database\Schema;

use PDO;

class SchemaManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTable(string $tableName, callable $callback): void
    {
        $blueprint = new TableBlueprint($tableName);
        $callback($blueprint);

        $this->pdo->exec($blueprint->toSql());
    }

    public function dropTable(string $tableName): void
    {
        $sql = sprintf('DROP TABLE IF EXISTS %s', $tableName);
        $this->pdo->exec($sql);
    }

    public function hasTable(string $tableName): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetch();
    }

    public function hasColumn(string $tableName, string $columnName): bool
    {
        $sql = "SHOW COLUMNS FROM {$tableName} LIKE ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$columnName]);
        return (bool) $stmt->fetch();
    }
}
