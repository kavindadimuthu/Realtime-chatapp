<?php
namespace app\core;

use PDO;
use PDOException;

/**
 * Database class that handles database connection and queries using PDO.
 * Implements Singleton Pattern to ensure a single instance of the Database connection.
 */
class Database
{
    private $dsn;        // Data Source Name (DSN) for the database connection
    private $user;       // Username for the database connection
    private $pass;       // Password for the database connection

    private $dbh;        // PDO instance for database interaction
    private $stmt;       // PDO statement object for prepared queries
    private static $instance = null;  // Singleton instance of the Database class

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the database connection using values from environment variables.
     */
    private function __construct()
    {
        $this->dsn = $_ENV['DB_DSN'] ?? '';
        $this->user = $_ENV['DB_USER'] ?? '';
        $this->pass = $_ENV['DB_PASSWORD'] ?? '';

        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        try {
            $this->dbh = new PDO($this->dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function prepare($sql)
    {
        try {
            $this->stmt = $this->dbh->prepare($sql);
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to prepare statement: ' . $e->getMessage());
        }
    }

    public function bind($param, $value, $type = null)
    {
        if ($type === null) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
        }

        try {
            $this->stmt->bindValue($param, $value, $type);
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to bind parameter: ' . $e->getMessage());
        }
    }

    public function execute()
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to execute query: ' . $e->getMessage());
        }
    }

    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC)
    {
        try {
            $this->execute();
            return $this->stmt->fetchAll($fetchStyle);
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to fetch all records: ' . $e->getMessage());
        }
    }

    public function fetch()
    {
        try {
            $this->execute();
            return $this->stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to fetch record: ' . $e->getMessage());
        }
    }

    public function fetchColumn()
    {
        try {
            $this->execute();
            return $this->stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to fetch column: ' . $e->getMessage());
        }
    }

    public function rowCount()
    {
        try {
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to get row count: ' . $e->getMessage());
        }
    }

    public function lastInsertId()
    {
        try {
            return $this->dbh->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to get last insert ID: ' . $e->getMessage());
        }
    }

    public function beginTransaction()
    {
        try {
            return $this->dbh->beginTransaction();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to begin transaction: ' . $e->getMessage());
        }
    }

    public function commit()
    {
        try {
            return $this->dbh->commit();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to commit transaction: ' . $e->getMessage());
        }
    }

    public function rollBack()
    {
        try {
            return $this->dbh->rollBack();
        } catch (PDOException $e) {
            $this->logError($e);
            throw new \Exception('Failed to roll back transaction: ' . $e->getMessage());
        }
    }

    public function executeWithParams($sql, $params = [])
    {
        $this->prepare($sql);
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        $this->execute();
        return $this->stmt;
    }

    private function logError($e)
    {
        error_log("[Database Error] " . $e->getMessage());
    }

    public function closeConnection()
    {
        $this->dbh = null;
        $this->stmt = null;
    }
}