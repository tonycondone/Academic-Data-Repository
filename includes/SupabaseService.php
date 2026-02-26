<?php
/**
 * Supabase Service Singleton
 * Provides a globally accessible database connection
 */

class SupabaseService {
    private static ?PDO $connection = null;

    /**
     * Get the singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
            self::setUserContext();
        }
        return self::$connection;
    }

    /**
     * Set the current user context for Row Level Security (RLS)
     */
    public static function setUserContext(): void {
        if (self::$connection === null || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            try {
                // Set the 'request.jwt.claims' setting so auth.uid() returns our user_id
                // This allows Supabase RLS policies to work with direct PDO connections
                $claims = json_encode(['sub' => (string)$userId, 'role' => $_SESSION['role'] ?? 'authenticated']);
                self::$connection->exec("SET LOCAL request.jwt.claims = '$claims'");
            } catch (PDOException $e) {
                // Ignore errors if we can't set context (e.g. not using Postgres)
                error_log("Failed to set user context for RLS: " . $e->getMessage());
            }
        }
    }

    /**
     * Create a new PDO connection using the Database class logic
     */
    private static function createConnection(): PDO {
        $database = new Database();
        return $database->createRawConnection();
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }
}
?>
