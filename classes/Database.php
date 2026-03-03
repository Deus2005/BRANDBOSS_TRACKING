<?php
/**
 * Database Connection Class
 * PDO-based with optimized settings for large datasets
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
                // Optimize for large data
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    /**
     * Execute a query with parameters
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single column value
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Insert and return last insert ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
    
    /**
     * Get row count with optional conditions
     */
    public function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Check if record exists
     */
    public function exists(string $table, string $where, array $params = []): bool {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Paginated query for large datasets
     */
    public function paginate(string $sql, array $params, int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        // Get total count - need to find the MAIN FROM clause (not subqueries)
        // First, remove ORDER BY clause (including FIELD function and multi-line)
        $countSql = preg_replace('/ORDER BY[\s\S]*$/i', '', $sql);
        
        // Remove any LIMIT clause
        $countSql = preg_replace('/LIMIT\s+\d+/i', '', $countSql);
        
        // Find the main FROM by tracking parentheses depth
        // The main FROM is the one at depth 0 (not inside any parentheses)
        $mainFromPos = $this->findMainFromPosition($countSql);
        
        if ($mainFromPos !== false) {
            $fromClause = substr($countSql, $mainFromPos);
            $countSql = "SELECT COUNT(*) " . $fromClause;
        } else {
            // Fallback: try simple replacement
            $countSql = preg_replace('/SELECT[\s\S]*?\sFROM\s/i', 'SELECT COUNT(*) FROM ', $countSql, 1);
        }
        
        $total = (int) $this->fetchColumn($countSql, $params);
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        
        // Get paginated data
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }
    
    /**
     * Find the position of the main FROM keyword (not inside subqueries)
     */
    private function findMainFromPosition(string $sql): int|false {
        $length = strlen($sql);
        $depth = 0; // Track parentheses depth
        
        for ($i = 0; $i < $length - 4; $i++) {
            $char = $sql[$i];
            
            // Track parentheses
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }
            
            // Only look for FROM at depth 0 (main query level)
            if ($depth === 0) {
                // Check if this position starts with FROM (case-insensitive)
                $substr = substr($sql, $i, 5);
                if (preg_match('/^\s*FROM\s/i', $substr) || 
                    (strtoupper(substr($sql, $i, 4)) === 'FROM' && 
                     ($i === 0 || preg_match('/\s/', $sql[$i-1])) &&
                     preg_match('/\s/', $sql[$i+4] ?? ' '))) {
                    // Found main FROM - return position of 'F'
                    // Skip any leading whitespace
                    while ($i < $length && ctype_space($sql[$i])) {
                        $i++;
                    }
                    return $i;
                }
            }
        }
        
        return false;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}