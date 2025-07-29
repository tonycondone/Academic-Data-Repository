<?php
/**
 * Core Utility Functions
 * Academic Dataset Collaboration Platform
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Format file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file type is allowed
 */
function isAllowedFileType($filename) {
    $extension = getFileExtension($filename);
    return in_array($extension, ALLOWED_FILE_TYPES);
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalFilename) {
    $extension = getFileExtension($originalFilename);
    $basename = pathinfo($originalFilename, PATHINFO_FILENAME);
    $timestamp = time();
    $random = generateRandomString(8);
    
    return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
}

/**
 * Log activity
 */
function logActivity($projectId, $userId, $action, $targetType, $targetId = null, $details = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO activity_log (project_id, user_id, action, target_type, target_id, details, ip_address, user_agent) 
                  VALUES (:project_id, :user_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':target_type', $targetType);
        $stmt->bindParam(':target_id', $targetId);
        $stmt->bindParam(':details', json_encode($details));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification
 */
function sendNotification($userId, $type, $title, $message, $data = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO notifications (user_id, type, title, message, data) 
                  VALUES (:user_id, :type, :title, :message, :data)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':data', json_encode($data));
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $whereClause = "WHERE user_id = :user_id";
        if ($unreadOnly) {
            $whereClause .= " AND is_read = 0";
        }
        
        $query = "SELECT * FROM notifications $whereClause ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 */
function markNotificationRead($notificationId, $userId) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Check if user has permission for project
 */
function hasProjectPermission($userId, $projectId, $permission = 'read') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if user is project owner
        $query = "SELECT owner_id FROM projects WHERE id = :project_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->execute();
        $project = $stmt->fetch();
        
        if ($project && $project['owner_id'] == $userId) {
            return true;
        }
        
        // Check project membership
        $query = "SELECT role, permissions FROM project_members 
                  WHERE project_id = :project_id AND user_id = :user_id AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $member = $stmt->fetch();
        
        if (!$member) {
            return false;
        }
        
        // Check specific permissions
        $permissions = json_decode($member['permissions'], true) ?? [];
        return isset($permissions[$permission]) ? $permissions[$permission] : ($member['role'] === 'owner');
        
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get file MIME type
 */
function getMimeType($filename) {
    $extension = getFileExtension($filename);
    
    $mimeTypes = [
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'json' => 'application/json',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?>