<?php

namespace TPT\ERP\Core;

/**
 * Database Abstraction Layer
 *
 * Provides a unified interface for database operations using PDO.
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;
    private array $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect(): void
    {
        $defaultConnection = $this->config['default'] ?? 'pgsql';
        $connectionConfig = $this->config['connections'][$defaultConnection] ?? [];

        $dsn = $this->buildDsn($defaultConnection, $connectionConfig);
        $username = $connectionConfig['username'] ?? '';
        $password = $connectionConfig['password'] ?? '';
        $options = $this->getPdoOptions($connectionConfig);

        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Build DSN string
     */
    private function buildDsn(string $driver, array $config): string
    {
        switch ($driver) {
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? '5432',
                    $config['database'] ?? '',
                    $config['username'] ?? '',
                    $config['password'] ?? ''
                );
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? '3306',
                    $config['database'] ?? '',
                    $config['charset'] ?? 'utf8mb4'
                );
            case 'sqlite':
                return 'sqlite:' . ($config['database'] ?? ':memory:');
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Get PDO options
     */
    private function getPdoOptions(array $config): array
    {
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (isset($config['charset'])) {
            $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$config['charset']}";
        }

        return $options;
    }

    /**
     * Execute a query and return results
     */
    public function query(string $sql, array $params = [], int $fetchMode = \PDO::FETCH_ASSOC): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll($fetchMode);
    }

    /**
     * Execute a query and return first result
     */
    public function queryOne(string $sql, array $params = [], int $fetchMode = \PDO::FETCH_ASSOC): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch($fetchMode);
        return $result ?: null;
    }

    /**
     * Execute a query and return single value
     */
    public function queryValue(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert data into table
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->execute($sql, $values);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update data in table
     */
    public function update(string $table, array $data, array $conditions): int
    {
        $setParts = [];
        $values = array_values($data);

        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }

        $whereParts = [];
        foreach (array_keys($conditions) as $column) {
            $whereParts[] = "{$column} = ?";
            $values[] = $conditions[$column];
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );

        return $this->execute($sql, $values);
    }

    /**
     * Delete data from table
     */
    public function delete(string $table, array $conditions): int
    {
        $whereParts = [];
        $values = [];

        foreach (array_keys($conditions) as $column) {
            $whereParts[] = "{$column} = ?";
            $values[] = $conditions[$column];
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $table,
            implode(' AND ', $whereParts)
        );

        return $this->execute($sql, $values);
    }

    /**
     * Find record by ID
     */
    public function find(string $table, int $id): ?array
    {
        return $this->queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    /**
     * Find records by conditions
     */
    public function findBy(string $table, array $conditions, array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $whereParts = [];
        $values = [];

        foreach (array_keys($conditions) as $column) {
            $whereParts[] = "{$column} = ?";
            $values[] = $conditions[$column];
        }

        $sql = "SELECT * FROM {$table}";

        if (!empty($whereParts)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        if (!empty($orderBy)) {
            $orderParts = [];
            foreach ($orderBy as $column => $direction) {
                $orderParts[] = "{$column} {$direction}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $this->query($sql, $values);
    }

    /**
     * Count records
     */
    public function count(string $table, array $conditions = []): int
    {
        $whereParts = [];
        $values = [];

        foreach (array_keys($conditions) as $column) {
            $whereParts[] = "{$column} = ?";
            $values[] = $conditions[$column];
        }

        $sql = "SELECT COUNT(*) FROM {$table}";

        if (!empty($whereParts)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        return (int) $this->queryValue($sql, $values);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollback();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Get PDO instance (for advanced usage)
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Quote identifier (table/column name)
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->pdo->quote($identifier);
    }

    /**
     * Close database connection
     */
    public function close(): void
    {
        $this->pdo = null;
        self::$instance = null;
    }
}
