<?php

declare(strict_types=1);

namespace RapidRest\Database\Migration;

use RapidRest\Database\Schema\SchemaManager;
use PDO;
use RuntimeException;

class MigrationService
{
    private PDO $pdo;
    private SchemaManager $schema;

    public function __construct(PDO $pdo, SchemaManager $schema)
    {
        $this->pdo = $pdo;
        $this->schema = $schema;
        $this->createMigrationsTable();
    }

    public function migrate(): void
    {
        $migrations = $this->getPendingMigrations();
        
        if (empty($migrations)) {
            echo "Nothing to migrate.\n";
            return;
        }

        $batch = $this->getNextBatchNumber();

        foreach ($migrations as $migration) {
            $this->runMigration($migration, $batch);
        }
    }

    public function rollback(): void
    {
        $lastBatch = $this->getLastBatchNumber();
        if (!$lastBatch) {
            echo "Nothing to rollback.\n";
            return;
        }

        $migrations = $this->getMigrationsInBatch($lastBatch);
        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
        }
    }

    public function getMigrationStatus(): array
    {
        $files = $this->getMigrationFiles();
        $completed = $this->getCompletedMigrations();
        $status = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $status[] = [
                'migration' => $name,
                'batch' => $completed[$name] ?? null,
                'status' => isset($completed[$name])
            ];
        }

        return $status;
    }

    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }

    private function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $completed = $this->getCompletedMigrations();
        
        return array_filter($files, function ($file) use ($completed) {
            return !isset($completed[pathinfo($file, PATHINFO_FILENAME)]);
        });
    }

    private function getMigrationFiles(): array
    {
        $path = getcwd() . '/migrations';
        if (!is_dir($path)) {
            return [];
        }

        $files = glob("$path/*.php");
        sort($files);
        return $files;
    }

    private function getCompletedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration, batch FROM migrations ORDER BY batch ASC, migration ASC");
        $migrations = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $migrations[$row['migration']] = $row['batch'];
        }
        
        return $migrations;
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM migrations");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row['batch'] ?? 0) + 1;
    }

    private function getLastBatchNumber(): ?int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM migrations");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['batch'] ?: null;
    }

    private function getMigrationsInBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY migration DESC");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function runMigration(string $file, int $batch): void
    {
        require_once $file;
        $class = $this->getMigrationClass($file);
        
        if (!class_exists($class)) {
            throw new RuntimeException("Migration class $class not found in $file");
        }

        $migration = new $class($this->schema);
        $migration->up();

        $name = pathinfo($file, PATHINFO_FILENAME);
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$name, $batch]);

        echo "Migrated: $name\n";
    }

    private function rollbackMigration(string $name): void
    {
        $file = getcwd() . "/migrations/$name.php";
        if (!file_exists($file)) {
            throw new RuntimeException("Migration file not found: $file");
        }

        require_once $file;
        $class = $this->getMigrationClass($file);
        
        if (!class_exists($class)) {
            throw new RuntimeException("Migration class $class not found in $file");
        }

        $migration = new $class($this->schema);
        $migration->down();

        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$name]);

        echo "Rolled back: $name\n";
    }

    private function getMigrationClass(string $file): string
    {
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/i', $content, $matches)) {
            return $matches[1];
        }
        throw new RuntimeException("Could not determine migration class name in $file");
    }
}
