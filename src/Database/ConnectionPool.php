<?php

declare(strict_types=1);

namespace RapidRest\Database;

use PDO;
use RuntimeException;

class ConnectionPool
{
    private array $connections = [];
    private array $inUse = [];
    private int $maxConnections;
    private array $config;

    public function __construct(array $config, int $maxConnections = 10)
    {
        $this->config = $config;
        $this->maxConnections = $maxConnections;
    }

    public function getConnection(): PDO
    {
        // Return existing idle connection if available
        foreach ($this->connections as $key => $connection) {
            if (!isset($this->inUse[$key]) || !$this->inUse[$key]) {
                $this->inUse[$key] = true;
                return $connection;
            }
        }

        // Create new connection if under limit
        if (count($this->connections) < $this->maxConnections) {
            $connection = $this->createConnection();
            $key = spl_object_hash($connection);
            $this->connections[$key] = $connection;
            $this->inUse[$key] = true;
            return $connection;
        }

        throw new RuntimeException('Connection pool exhausted');
    }

    public function releaseConnection(PDO $connection): void
    {
        $key = spl_object_hash($connection);
        if (isset($this->inUse[$key])) {
            $this->inUse[$key] = false;
        }
    }

    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $this->config['host'],
            $this->config['database']
        );

        $pdo = new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    }
}
