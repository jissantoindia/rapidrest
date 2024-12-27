<?php

namespace RapidRest\Console\Commands;

use RapidRest\Database\Migration\MigrationService;

class MigrationCommand
{
    private $migrationService;

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    public function create(string $name): void
    {
        $timestamp = date('YmdHis');
        $className = $this->formatClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $path = getcwd() . '/migrations/' . $filename;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $template = $this->getMigrationTemplate($className);
        file_put_contents($path, $template);
        echo "Created Migration: {$filename}\n";
    }

    public function migrate(): void
    {
        try {
            $this->migrationService->migrate();
            echo "Migrations completed successfully.\n";
        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
        }
    }

    public function rollback(): void
    {
        try {
            $this->migrationService->rollback();
            echo "Rollback completed successfully.\n";
        } catch (\Exception $e) {
            echo "Rollback failed: " . $e->getMessage() . "\n";
        }
    }

    public function refresh(): void
    {
        $this->rollback();
        $this->migrate();
        echo "Database refresh completed.\n";
    }

    public function status(): void
    {
        $migrations = $this->migrationService->getMigrationStatus();
        echo "\nMigration Status:\n";
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-40s %-20s %s\n", 'Migration', 'Batch', 'Status');
        echo str_repeat('-', 80) . "\n";

        foreach ($migrations as $migration) {
            echo sprintf(
                "%-40s %-20s %s\n",
                $migration['migration'],
                $migration['batch'] ?? 'Pending',
                $migration['status'] ? 'Completed' : 'Pending'
            );
        }
    }

    private function formatClassName(string $name): string
    {
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    private function getMigrationTemplate(string $className): string
    {
        return <<<PHP
<?php

use RapidRest\Database\Migration\Migration;
use RapidRest\Database\Schema\Blueprint;
use RapidRest\Database\Schema\Schema;

class {$className} extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
}
PHP;
    }
}
