<?php

declare(strict_types=1);

namespace RapidRest\Database\Schema;

class TableBlueprint
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(): self
    {
        return $this->column('id', 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
    }

    public function string(string $name, int $length = 255): self
    {
        return $this->column($name, "VARCHAR($length)");
    }

    public function text(string $name): self
    {
        return $this->column($name, 'TEXT');
    }

    public function integer(string $name): self
    {
        return $this->column($name, 'INT');
    }

    public function bigInteger(string $name): self
    {
        return $this->column($name, 'BIGINT');
    }

    public function boolean(string $name): self
    {
        return $this->column($name, 'BOOLEAN');
    }

    public function timestamp(string $name): self
    {
        return $this->column($name, 'TIMESTAMP');
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        return $this->timestamp('updated_at')->nullable();
    }

    public function column(string $name, string $type): self
    {
        $this->columns[$name] = [
            'type' => $type,
            'nullable' => false,
            'default' => null,
        ];
        return $this;
    }

    public function nullable(): self
    {
        $name = array_key_last($this->columns);
        $this->columns[$name]['nullable'] = true;
        return $this;
    }

    public function default($value): self
    {
        $name = array_key_last($this->columns);
        $this->columns[$name]['default'] = $value;
        return $this;
    }

    public function index(string $column, ?string $name = null): self
    {
        $name = $name ?? $this->table . '_' . $column . '_idx';
        $this->indexes[] = [
            'type' => 'INDEX',
            'name' => $name,
            'columns' => [$column],
        ];
        return $this;
    }

    public function unique(string $column, ?string $name = null): self
    {
        $name = $name ?? $this->table . '_' . $column . '_unique';
        $this->indexes[] = [
            'type' => 'UNIQUE',
            'name' => $name,
            'columns' => [$column],
        ];
        return $this;
    }

    public function foreignKey(string $column, string $referenceTable, string $referenceColumn = 'id'): self
    {
        $name = $this->table . '_' . $column . '_fk';
        $this->foreignKeys[] = [
            'name' => $name,
            'column' => $column,
            'reference_table' => $referenceTable,
            'reference_column' => $referenceColumn,
        ];
        return $this;
    }

    public function toSql(): string
    {
        $parts = [];

        // Columns
        foreach ($this->columns as $name => $column) {
            $part = "$name {$column['type']}";
            if (!$column['nullable']) {
                $part .= ' NOT NULL';
            }
            if ($column['default'] !== null) {
                $part .= ' DEFAULT ' . $this->quote($column['default']);
            }
            $parts[] = $part;
        }

        // Indexes
        foreach ($this->indexes as $index) {
            $columns = implode(', ', $index['columns']);
            $parts[] = "{$index['type']} {$index['name']} ($columns)";
        }

        // Foreign Keys
        foreach ($this->foreignKeys as $fk) {
            $parts[] = sprintf(
                'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)',
                $fk['name'],
                $fk['column'],
                $fk['reference_table'],
                $fk['reference_column']
            );
        }

        return sprintf(
            'CREATE TABLE %s (%s)',
            $this->table,
            implode(', ', $parts)
        );
    }

    private function quote($value): string
    {
        if (is_string($value)) {
            return "'$value'";
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value === null) {
            return 'NULL';
        }
        return (string) $value;
    }
}
