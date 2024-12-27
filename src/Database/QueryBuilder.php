<?php

declare(strict_types=1);

namespace RapidRest\Database;

class QueryBuilder
{
    private string $table = '';
    private array $select = ['*'];
    private array $where = [];
    private array $params = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->where[] = [$column, $operator, $value];
        $this->params[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function toSql(): array
    {
        $sql = ['SELECT ' . implode(', ', $this->select)];
        $sql[] = 'FROM ' . $this->table;

        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as [$column, $operator, $_]) {
                $conditions[] = "$column $operator ?";
            }
            $sql[] = 'WHERE ' . implode(' AND ', $conditions);
        }

        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as [$column, $direction]) {
                $orders[] = "$column $direction";
            }
            $sql[] = 'ORDER BY ' . implode(', ', $orders);
        }

        if ($this->limit !== null) {
            $sql[] = 'LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql[] = 'OFFSET ' . $this->offset;
        }

        return [
            'sql' => implode(' ', $sql),
            'params' => $this->params
        ];
    }

    public function insert(array $data): array
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return [
            'sql' => $sql,
            'params' => $values
        ];
    }

    public function update(array $data): array
    {
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s',
            $this->table,
            implode(', ', $sets)
        );

        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as [$column, $operator, $_]) {
                $conditions[] = "$column $operator ?";
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
            $params = array_merge($params, $this->params);
        }

        return [
            'sql' => $sql,
            'params' => $params
        ];
    }

    public function delete(): array
    {
        $sql = sprintf('DELETE FROM %s', $this->table);

        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as [$column, $operator, $_]) {
                $conditions[] = "$column $operator ?";
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        return [
            'sql' => $sql,
            'params' => $this->params
        ];
    }
}
