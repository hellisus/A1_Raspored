<?php
require_once 'config_ajax.php';

class CRUD_ajax {
    private $pdo;
    private $table;
    private $year;
    
    public function __construct($year) {
        global $pdo;
        $this->pdo = $pdo;
        $this->year = $year;
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
    
    public function select($columns = [], $where = [], $sql = '', $params = []) {
        try {
            if (!empty($sql)) {
                // Koristi custom SQL
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Koristi standardni CRUD
                $sql = "SELECT * FROM {$this->table}";
                $params = [];
                
                if (!empty($where)) {
                    $conditions = [];
                    foreach ($where as $key => $value) {
                        $conditions[] = "$key = ?";
                        $params[] = $value;
                    }
                    $sql .= " WHERE " . implode(" AND ", $conditions);
                }
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function insert($data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Insert error: " . $e->getMessage());
        }
    }
    
    public function update($data, $where) {
        try {
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "$key = :where_$key";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            foreach ($where as $key => $value) {
                $stmt->bindValue(":where_$key", $value);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Update error: " . $e->getMessage());
        }
    }
    
    public function delete($where) {
        try {
            $whereClause = [];
            foreach ($where as $key => $value) {
                $whereClause[] = "$key = :$key";
            }
            
            $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereClause);
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($where as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Delete error: " . $e->getMessage());
        }
    }
}
?>
