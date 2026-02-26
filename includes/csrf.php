<?php
/**
 * CSRF Protection Helper Functions
 * Provides Cross-Site Request Forgery protection for forms
 */

/**
 * Generate a CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token input field
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Check CSRF token for POST requests
 */
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}

/**
 * Store CSRF token in database (for enhanced security)
 */
function storeCSRFTokenInDB($pdo, $userId = null) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
    
    try {
        $stmt = $pdo->prepare("INSERT INTO csrf_tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$token, $userId, $expiresAt]);
        return $token;
    } catch(PDOException $e) {
        return generateCSRFToken(); // Fallback to session-based token
    }
}

/**
 * Validate CSRF token from database
 */
function validateCSRFTokenFromDB($pdo, $token, $userId = null) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM csrf_tokens WHERE token = ? AND expires_at > NOW() AND is_used = FALSE AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$token, $userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE csrf_tokens SET is_used = TRUE WHERE id = ?");
            $stmt->execute([$result['id']]);
            return true;
        }
        return false;
    } catch(PDOException $e) {
        return validateCSRFToken($token); // Fallback to session-based validation
    }
}

/**
 * Clean expired CSRF tokens from database
 */
function cleanExpiredCSRFTokens($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM csrf_tokens WHERE expires_at < NOW() OR is_used = TRUE");
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail - not critical
    }
}
?>
