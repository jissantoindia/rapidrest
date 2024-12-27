<?php

declare(strict_types=1);

namespace RapidRest\Database\Migration;

use PDO;
use RapidRest\Database\Schema\SchemaManager;

class Migrator
{
    private PDO $pdo;
    private SchemaManager $schema;
    private string $migrationsPath;
    private string $table = 'migrations';

    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->schema = new SchemaManager($pdo);
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->createTable($this->table, function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }

    public function migrate(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();
        $migrations = array_diff($files, $ran);
        $batch = $this->getNextBatchNumber();
        $migrated = [];

        foreach ($migrations as $migration) {
            $this->runMigration($migration, $batch);
            $migrated[] = $migration;
        }

        return $migrated;
    }

    public function rollback(): array
    {
        $lastBatch = $this->getLastBatchNumber();
        $migrations = $this->getMigrationsForBatch($lastBatch);
        $rolledBack = [];

        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }

        return $rolledBack;
    }

    private function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);
        return array_filter($files, function ($file) {
            return !in_array($file, ['.', '..']) && str_ends_with($file, '.php');
        });
    }

    private function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    private function getLastBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM {$this->table}");
        return (int) $stmt->fetchColumn() ?: 0;
    }

    private function getMigrationsForBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM {$this->table} WHERE batch = ?");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function runMigration(string $file, int $batch): void
    {
        require_once $this->migrationsPath . '/' . $file;
        $class = $this->getMigrationClass($file);
        $migration = new $class($this->schema);
        
        $migration->up();

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$file, $batch]);
    }

    private function rollbackMigration(string $file): void
    {
        require_once $this->migrationsPath . '/' . $file;
        $class = $this->getMigrationClass($file);
        $migration = new $class($this->schema);
        
        $migration->down();

        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE migration = ?");
        $stmt->execute([$file]);
    }

    private function getMigrationClass(string $file): string
    {
        return 'Migration_' . basename($file, '.php');
    }
}
