<?php

/**
 * BaseModel Class
 *
 * Provides robust and reusable database interaction methods using the Database class.
 * Supports CRUD, count, join, custom queries, and utility helpers.
 * All database communication is routed through the Database class.
 *
 * Usage:
 *   - Extend this class in your model and set $table property for strict model usage.
 *   - Or instantiate directly in a controller and provide $table as a parameter to methods.
 */

namespace app\core;

use app\core\Database;
use PDO;
use PDOException;
use Exception;

class BaseModel
{
    /** @var Database $db Singleton instance of the Database class */
    protected $db;

    /** @var string|null $table The name of the table associated with the model */
    protected $table = null;

    /**
     * BaseModel constructor.
     * Initializes the Database instance.
     * Optionally set the table name.
     *
     * @param string|null $table
     */
    public function __construct($table = null)
    {
        $this->db = Database::getInstance();
        if ($table !== null) {
            $this->table = $table;
        }
    }

    // =========================
    // CRUD OPERATIONS
    // =========================

    /**
     * Insert a new record into the table.
     *
     * @param array $data Key-value pairs of column => value.
     * @param string|null $table Optional table name override.
     * @return int|false Last insert ID on success, false on failure.
     */
    public function create(array $data, $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for insert."));
            return false;
        }
        if (empty($data)) {
            $this->logError(new Exception("No data provided for insert."));
            return false;
        }
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$tableName} ($columns) VALUES ($placeholders)";
        try {
            $this->db->executeWithParams($sql, $data);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Retrieve multiple records from the table.
     *
     * @param array $options ['columns', 'order', 'limit', 'offset', 'search', 'searchColumns', 'filters']
     * @param string|null $table Optional table name override.
     * @return array|false Array of records or false on failure.
     */
    public function read(array $options = [], $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for read."));
            return false;
        }
        $columns = !empty($options['columns']) ? implode(', ', $options['columns']) : '*';
        $sql = "SELECT $columns FROM {$tableName}";
        $params = [];

        // WHERE clause from filters
        $whereClause = $this->buildWhereClause(isset($options['filters']) ? $options['filters'] : []);

        // Search support
        if (!empty($options['search']) && !empty($options['searchColumns'])) {
            $searchConditions = array_map(fn($col) => "$col LIKE :search", $options['searchColumns']);
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "(" . implode(" OR ", $searchConditions) . ")";
            $params['search'] = "%" . $options['search'] . "%";
        }

        $sql .= " $whereClause";
        $sql .= $this->buildOrderAndLimit($options);

        // Add filter params
        if (!empty($options['filters'])) {
            $params = array_merge($params, $this->buildWhereParams($options['filters']));
        }

        try {
            $this->db->executeWithParams($sql, $params);
            return $this->db->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Retrieve a single record from the table.
     *
     * @param array $options Additional options.
     * @param string|null $table Optional table name override.
     * @return array|false Single record or false on failure.
     */
    public function readOne(array $options = [], $table = null)
    {
        $options['limit'] = 1;
        $result = $this->read($options, $table);
        return $result && isset($result[0]) ? $result[0] : false;
    }

    /**
     * Update records in the table.
     *
     * @param array $data Key-value pairs of columns to update.
     * @param array $options ['filters' => [...]]
     * @param string|null $table Optional table name override.
     * @return bool Success or failure.
     */
    public function update(array $data, array $options = [], $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for update."));
            return false;
        }
        if (empty($data)) {
            $this->logError(new Exception("No data provided for update."));
            return false;
        }
        $setClause = implode(", ", array_map(fn($key) => "$key = :$key", array_keys($data)));
        $whereClause = $this->buildWhereClause(isset($options['filters']) ? $options['filters'] : [], 'cond_');
        $sql = "UPDATE {$tableName} SET $setClause $whereClause";
        $params = array_merge($data, $this->buildWhereParams(isset($options['filters']) ? $options['filters'] : [], 'cond_'));
        try {
            $this->db->executeWithParams($sql, $params);
            return true;
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Delete records from the table.
     *
     * @param array $options ['filters' => [...]]
     * @param string|null $table Optional table name override.
     * @return bool Success or failure.
     */
    public function delete(array $options = [], $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for delete."));
            return false;
        }
        $whereClause = $this->buildWhereClause(isset($options['filters']) ? $options['filters'] : []);
        if (!$whereClause) {
            $this->logError(new Exception("Delete operation requires at least one filter."));
            return false;
        }
        $sql = "DELETE FROM {$tableName} $whereClause";
        $params = $this->buildWhereParams(isset($options['filters']) ? $options['filters'] : []);
        try {
            $this->db->executeWithParams($sql, $params);
            return true;
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    // =========================
    // ADVANCED QUERIES
    // =========================

    /**
     * Count records matching filters, with optional joins, search, etc.
     *
     * @param array $options ['search', 'searchColumns', 'filters']
     * @param array $joins Array of join definitions.
     * @param string|null $table Optional table name override.
     * @return int|false Number of records or false on failure.
     */
    public function count(array $options = [], array $joins = [], $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for count."));
            return false;
        }
        $sql = "SELECT COUNT(*) as total FROM {$tableName}";
        $params = [];

        // JOIN clauses
        foreach ($joins as $join) {
            $type = strtoupper($join['type'] ?? 'INNER');
            $joinTable = $join['table'] ?? '';
            $on = $join['on'] ?? '';
            if ($joinTable && $on) {
                $sql .= " $type JOIN $joinTable ON $on";
            }
        }

        // WHERE clause from filters
        $whereClause = $this->buildWhereClause(isset($options['filters']) ? $options['filters'] : []);

        // Search
        if (!empty($options['search']) && !empty($options['searchColumns'])) {
            $searchConditions = array_map(fn($col) => "$col LIKE :search", $options['searchColumns']);
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "(" . implode(" OR ", $searchConditions) . ")";
            $params['search'] = "%" . $options['search'] . "%";
        }

        $sql .= " $whereClause";

        // Add filter params
        if (!empty($options['filters'])) {
            $params = array_merge($params, $this->buildWhereParams($options['filters']));
        }

        try {
            $this->db->executeWithParams($sql, $params);
            $result = $this->db->fetch(PDO::FETCH_ASSOC);
            return isset($result['total']) ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Retrieve records with JOINs.
     *
     * @param array $joins Array of join definitions.
     * @param array $options ['columns', 'order', 'limit', 'offset', 'search', 'searchColumns', 'filters']
     * @param string|null $table Optional table name override.
     * @return array|false Array of records or false on failure.
     */
    public function readWithJoin(array $joins = [], array $options = [], $table = null)
    {
        $tableName = $table ?? $this->table;
        if (!$tableName) {
            $this->logError(new Exception("Table name not specified for readWithJoin."));
            return false;
        }
        $columns = !empty($options['columns']) ? implode(', ', $options['columns']) : '*';
        $sql = "SELECT $columns FROM {$tableName}";
        $params = [];

        // JOIN clauses
        foreach ($joins as $join) {
            $type = strtoupper($join['type'] ?? 'INNER');
            $joinTable = $join['table'] ?? '';
            $on = $join['on'] ?? '';
            if ($joinTable && $on) {
                $sql .= " $type JOIN $joinTable ON $on";
            }
        }

        // WHERE clause from filters
        $whereClause = $this->buildWhereClause(isset($options['filters']) ? $options['filters'] : []);

        // Search
        if (!empty($options['search']) && !empty($options['searchColumns'])) {
            $searchConditions = array_map(fn($col) => "$col LIKE :search", $options['searchColumns']);
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "(" . implode(" OR ", $searchConditions) . ")";
            $params['search'] = "%" . $options['search'] . "%";
        }

        $sql .= " $whereClause";
        $sql .= $this->buildOrderAndLimit($options);

        // Add filter params
        if (!empty($options['filters'])) {
            $params = array_merge($params, $this->buildWhereParams($options['filters']));
        }

        try {
            $this->db->executeWithParams($sql, $params);
            return $this->db->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Execute a custom SQL query with optional parameters.
     *
     * @param string $sql The SQL query.
     * @param array $params Parameters to bind.
     * @param bool $fetchAll Fetch all rows (true) or single row (false).
     * @return mixed Array of results, single result, or boolean for non-select.
     */
    public function executeCustomQuery($sql, $params = [], $fetchAll = true)
    {
        try {
            $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            $this->db->execute();

            $trimmedSql = preg_replace('/^\s*\(*\s*/', '', trim($sql));
            if (stripos($trimmedSql, 'SELECT') === 0) {
                return $fetchAll ? $this->db->fetchAll(PDO::FETCH_ASSOC) : $this->db->fetch();
            }
            return true;
        } catch (PDOException $e) {
            $this->logError($e);
            return false;
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    // =========================
    // UTILITY & HELPER METHODS
    // =========================

    /**
     * Build a WHERE clause from an array of filters.
     *
     * @param array $filters Key-value pairs for WHERE.
     * @param string $prefix Prefix for parameter keys.
     * @return string WHERE clause or empty string.
     */
    protected function buildWhereClause(array $filters, $prefix = '')
    {
        if (empty($filters)) {
            return '';
        }
        $clauses = [];
        foreach ($filters as $key => $value) {
            if (preg_match('/^([a-zA-Z0-9_\.]+)\s*(>=|<=|>|<|!=|=)$/', $key, $matches)) {
                $col = $matches[1];
                $op = $matches[2];
                $param = $prefix . str_replace(['.', '>=', '<=', '>', '<', '!=', '='], ['_', 'gte', 'lte', 'gt', 'lt', 'ne', 'eq'], $key);
                $clauses[] = "$col $op :$param";
            } else {
                $safeCol = str_replace('.', '_', $key);
                $clauses[] = "$key = :$prefix$safeCol";
            }
        }
        return 'WHERE ' . implode(' AND ', $clauses);
    }

    /**
     * Build parameters array for WHERE clause from filters.
     *
     * @param array $filters
     * @param string $prefix
     * @return array
     */
    protected function buildWhereParams(array $filters, $prefix = '')
    {
        $params = [];
        foreach ($filters as $key => $value) {
            if (preg_match('/^([a-zA-Z0-9_\.]+)\s*(>=|<=|>|<|!=|=)$/', $key, $matches)) {
                $col = $matches[1];
                $op = $matches[2];
                $param = $prefix . str_replace(['.', '>=', '<=', '>', '<', '!=', '='], ['_', 'gte', 'lte', 'gt', 'lt', 'ne', 'eq'], $key);
                $params[$param] = $value;
            } else {
                $safeCol = str_replace('.', '_', $key);
                $params[$prefix . $safeCol] = $value;
            }
        }
        return $params;
    }

    /**
     * Build ORDER BY, LIMIT, and OFFSET clause from options.
     *
     * @param array $options
     * @return string
     */
    protected function buildOrderAndLimit(array $options)
    {
        $order = isset($options['order']) ? " ORDER BY {$options['order']}" : "";
        $limit = isset($options['limit']) ? " LIMIT {$options['limit']}" : "";
        $offset = isset($options['offset']) ? " OFFSET {$options['offset']}" : "";
        return $order . $limit . $offset;
    }

    /**
     * Get the last inserted ID from the database.
     *
     * @return int|string Last insert ID.
     */
    public function getLastInsertId()
    {
        try {
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            $this->logError($e);
            return 0;
        }
    }

    /**
     * Start a database transaction.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        try {
            return $this->db->beginTransaction();
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Commit a database transaction.
     *
     * @return bool
     */
    public function commit()
    {
        try {
            return $this->db->commit();
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Roll back a database transaction.
     *
     * @return bool
     */
    public function rollBack()
    {
        try {
            return $this->db->rollBack();
        } catch (Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Log errors for debugging and monitoring.
     *
     * @param Exception $e
     */
    protected function logError($e)
    {
        error_log("[BaseModel Error] " . $e->getMessage());
    }
}