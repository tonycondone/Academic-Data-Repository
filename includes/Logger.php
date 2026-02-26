<?php
/**
 * Logger Service
 * Production-ready error and activity logging system
 */

class Logger {
    private const LOG_DIR = ROOT_PATH . 'logs/';
    private const ERROR_LOG = self::LOG_DIR . 'error.log';
    private const ACCESS_LOG = self::LOG_DIR . 'access.log';
    private const SECURITY_LOG = self::LOG_DIR . 'security.log';

    /**
     * Initialize logging environment
     */
    public static function init(): void {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0755, true);
        }
        
        // Ensure .htaccess exists to protect logs
        $htaccess = self::LOG_DIR . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all");
        }
    }

    /**
     * Log an error
     */
    public static function error(string $message, array $context = []): void {
        self::log(self::ERROR_LOG, 'ERROR', $message, $context);
    }

    /**
     * Log a security event
     */
    public static function security(string $message, array $context = []): void {
        self::log(self::SECURITY_LOG, 'SECURITY', $message, $context);
    }

    /**
     * Log an access/activity event
     */
    public static function info(string $message, array $context = []): void {
        self::log(self::ACCESS_LOG, 'INFO', $message, $context);
    }

    /**
     * Internal logging logic
     */
    private static function log(string $file, string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        
        $logEntry = "[$timestamp] [$level] [User: $userId] [IP: $ip] $message$contextStr" . PHP_EOL;
        
        error_log($logEntry, 3, $file);

        // Also log to database if it's a significant activity
        if ($level === 'SECURITY' || ($level === 'INFO' && isset($context['database_log']))) {
            self::logToDatabase($level, $message, $context);
        }
    }

    /**
     * Log to the activity_log database table
     */
    private static function logToDatabase(string $level, string $message, array $context): void {
        try {
            $pdo = SupabaseService::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $userId = $_SESSION['user_id'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $details = json_encode(array_merge(['message' => $message], $context));
            
            $stmt->execute([
                $userId,
                $level,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $userAgent
            ]);
        } catch (Exception $e) {
            // Fallback to file logging if DB fails to avoid infinite loops
            error_log("DB Logging failed: " . $e->getMessage(), 3, self::ERROR_LOG);
        }
    }
}
?>
