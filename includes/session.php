<?php
/**
 * Enhanced Session Management System
 * Provides secure session handling with timeout and security features
 */

/**
 * Start secure session with enhanced security
 */
function startSecureSession() {
    // Session configuration for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session timeout to 30 minutes (1800 seconds)
    ini_set('session.gc_maxlifetime', 1800);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Check session timeout (30 minutes of inactivity)
 */
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes in seconds
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session has expired
            destroySession();
            header('Location: login.php?timeout=1');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy session securely
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['name']) && isset($_SESSION['role']);
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    checkSessionTimeout();
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role']
    ];
}

/**
 * Login user and create session
 */
function loginUser($user) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();
    
    // Store additional security info
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Logout user
 */
function logoutUser() {
    destroySession();
}

/**
 * Check for session hijacking
 */
function checkSessionSecurity() {
    if (isLoggedIn()) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check if IP address has changed (optional - can be disabled for mobile users)
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $currentIP) {
            // Log security event
            error_log("Session security warning: IP change for user " . $_SESSION['user_id'] . " from " . $_SESSION['user_ip'] . " to " . $currentIP);
            // Optionally destroy session for high-security applications
            // destroySession();
            // header('Location: login.php?security=1');
            // exit;
        }
        
        // Check if user agent has changed
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
            error_log("Session security warning: User agent change for user " . $_SESSION['user_id']);
            // Optionally destroy session
            // destroySession();
            // header('Location: login.php?security=1');
            // exit;
        }
    }
}

/**
 * Get session time remaining in minutes
 */
function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $timeout = 1800; // 30 minutes
    $elapsed = time() - $_SESSION['last_activity'];
    $remaining = $timeout - $elapsed;
    
    return max(0, floor($remaining / 60));
}

/**
 * Store session in database for enhanced tracking
 */
function storeSessionInDB($pdo, $userId) {
    try {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
        
        // Clean old sessions for this user
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new session
        // PostgreSQL UPSERT syntax
        $stmt = $pdo->prepare("INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT (id) DO UPDATE SET expires_at = ?, is_active = TRUE");
        $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent, $expiresAt, $expiresAt]);
        
    } catch(PDOException $e) {
        // Silently fail - session will still work without DB storage
        error_log("Session DB storage failed: " . $e->getMessage());
    }
}

/**
 * Clean expired sessions from database
 */
function cleanExpiredSessions($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW() OR is_active = FALSE");
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail
    }
}

/**
 * Initialize session system
 */
function initializeSession($pdo = null) {
    startSecureSession();
    checkSessionSecurity();
    
    if (isLoggedIn()) {
        checkSessionTimeout();
        
        // Store session in database if PDO connection is available
        if ($pdo && isset($_SESSION['user_id'])) {
            storeSessionInDB($pdo, $_SESSION['user_id']);
        }
    }
    
    // Clean expired sessions and CSRF tokens periodically
    if ($pdo && rand(1, 100) === 1) { // 1% chance to run cleanup
        cleanExpiredSessions($pdo);
        if (function_exists('cleanExpiredCSRFTokens')) {
            cleanExpiredCSRFTokens($pdo);
        }
    }
}
?>
