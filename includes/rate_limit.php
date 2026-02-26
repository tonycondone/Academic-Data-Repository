<?php
/**
 * Rate Limiting Service
 * Protects authentication endpoints from brute-force attacks
 */

class RateLimiter {
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes in seconds

    /**
     * Check if the current IP is locked out
     */
    public static function isLockedOut(string $username = null): bool {
        $ip = $_SERVER['REMOTE_ADDR'];
        $pdo = SupabaseService::getConnection();
        
        // Check attempts in the last 15 minutes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE (ip_address = ? OR username = ?) 
            AND attempt_time > NOW() - INTERVAL '15 minutes' 
            AND success = FALSE
        ");
        $stmt->execute([$ip, $username]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Record a login attempt
     */
    public static function recordAttempt(string $username, bool $success): void {
        $ip = $_SERVER['REMOTE_ADDR'];
        $pdo = SupabaseService::getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (ip_address, username, success) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip, $username, $success ? 'TRUE' : 'FALSE']);
    }

    /**
     * Get remaining attempts
     */
    public static function getRemainingAttempts(string $username = null): int {
        $ip = $_SERVER['REMOTE_ADDR'];
        $pdo = SupabaseService::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE (ip_address = ? OR username = ?) 
            AND attempt_time > NOW() - INTERVAL '15 minutes' 
            AND success = FALSE
        ");
        $stmt->execute([$ip, $username]);
        $attempts = $stmt->fetchColumn();
        
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }
}
?>
