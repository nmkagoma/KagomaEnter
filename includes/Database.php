<?php
/**
 * Database Connection Class
 */

class Database {
    private static $instance = null;
    private $conn;
    
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "KagomaEnter";
    
    private function __construct() {
        // Try connecting without socket first (works for most environments)
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        // If connection failed, try with common socket paths
        if ($this->conn->connect_error) {
            $socketPaths = [
                '/opt/lampp/var/mysql/mysql.sock',
                '/var/run/mysqld/mysqld.sock',
                '/tmp/mysql.sock',
                '/var/mysql/mysql.sock'
            ];
            
            $connected = false;
            foreach ($socketPaths as $socket) {
                if (file_exists($socket)) {
                    $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname, 0, $socket);
                    if (!$this->conn->connect_error) {
                        $connected = true;
                        break;
                    }
                }
            }
            
            if (!$connected && !$this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    /**
     * Fetch a single row from the database
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|null
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ?: null;
    }
    
    /**
     * Fetch all rows from the database
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        
        return $rows;
    }
    
    /**
     * Insert a row into the database
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false The insert ID or false on failure
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $types = '';
        $values = [];
        foreach ($data as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $success ? $insertId : false;
    }
    
    /**
     * Update rows in the database
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $params = []) {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setClauses);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        $types = '';
        $values = [];
        foreach ($data as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    /**
     * Delete rows from the database
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    /**
     * Get the last insert ID
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
    
    public function insertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    public function error() {
        return $this->conn->error;
    }
    
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
}

