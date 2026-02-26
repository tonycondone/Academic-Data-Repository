<?php
/**
 * Database Session Handler
 * Stores PHP sessions in PostgreSQL database
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function open($savePath, $sessionName): bool {
        return true;
    }
    
    public function close(): bool {
        return true;
    }
    
    public function read($id): string|false {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :id AND last_access > NOW() - INTERVAL '30 minutes'");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['data'];
            }
            return '';
        } catch (PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }
    
    public function write($id, $data): bool {
        try {
            // Upsert (PostgreSQL specific)
            $sql = "INSERT INTO sessions (id, data, last_access) 
                    VALUES (:id, :data, NOW()) 
                    ON CONFLICT (id) DO UPDATE 
                    SET data = EXCLUDED.data, last_access = EXCLUDED.last_access";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':data' => $data
            ]);
        } catch (PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }
    
    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    public function gc($max_lifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < NOW() - INTERVAL '30 minutes'"); // Or use $max_lifetime
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Session gc error: " . $e->getMessage());
            return false;
        }
    }
}
