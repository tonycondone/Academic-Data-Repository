<?php
/**
 * Authentication System
 * Academic Dataset Collaboration Platform
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = SupabaseService::getConnection();
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            $query = "SELECT id, name, email, password, role, is_active 
                      FROM users WHERE email = :email AND is_active = true";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Create session
                $this->createSession($user);
                
                // Log activity
                logActivity(0, $user['id'], 'login', 'user', $user['id']);
                
                return [
                    'success' => true,
                    'user' => $user
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    /**
     * Register new user
     */
    public function register($userData) {
        try {
            // Validate input
            $validation = $this->validateRegistration($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Check if email already exists
            if ($this->userExists($userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }
            
            // Hash password
            $passwordHash = hashPassword($userData['password']);
            
            // Insert user
            $query = "INSERT INTO users (name, email, password, role) 
                      VALUES (:name, :email, :password, :role)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $userData['name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':role', $userData['role']);
            
            if ($stmt->execute()) {
                $userId = $this->db->lastInsertId();
                
                // Send welcome notification
                sendNotification(
                    $userId,
                    'welcome',
                    'Welcome to Academic Collaboration Platform',
                    'Your account has been created successfully. You can now start collaborating on data projects.'
                );
                
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $userId
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log activity
            logActivity(0, $_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
            
            // Destroy session
            session_unset();
            session_destroy();
        }
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $query = "SELECT id, name, email, role, created_at, last_login 
                      FROM users WHERE id = :user_id AND is_active = true";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check user role
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if user is faculty
     */
    public function isFaculty() {
        return $this->hasRole('faculty');
    }
    
    /**
     * Check if user is student
     */
    public function isStudent() {
        return $this->hasRole('student');
    }
    
    /**
     * Require login
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            redirect('dashboard.php', 'Access denied. Insufficient permissions.', 'error');
        }
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireRole('admin');
    }
    
    /**
     * Require faculty access
     */
    public function requireFaculty() {
        $this->requireLogin();
        
        if (!$this->isFaculty() && !$this->isAdmin()) {
            redirect('dashboard.php', 'Access denied. Faculty access required.', 'error');
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $query = "SELECT password FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user || !verifyPassword($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Update password
            $newPasswordHash = hashPassword($newPassword);
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $newPasswordHash);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                // Log activity
                logActivity(0, $userId, 'password_change', 'user', $userId);
                
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to change password'
            ];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to change password'
            ];
        }
    }
    
    /**
     * Private helper methods
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
    }
    
    private function updateLastLogin($userId) {
        try {
            $query = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    private function userExists($email) {
        try {
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("User exists check error: " . $e->getMessage());
            return true; // Assume exists to prevent duplicate registration
        }
    }
    
    private function validateRegistration($data) {
        // Required fields
        $required = ['name', 'email', 'password', 'role'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => ucfirst($field) . ' is required'
                ];
            }
        }
        
        // Validate email
        if (!validateEmail($data['email'])) {
            return [
                'valid' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        // Validate password length
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            return [
                'valid' => false,
                'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
            ];
        }
        
        // Validate role
        if (!in_array($data['role'], ['admin', 'faculty', 'student', 'user'])) {
            return [
                'valid' => false,
                'message' => 'Invalid role selected'
            ];
        }
        
        return ['valid' => true];
    }
}

// Create global auth instance
try {
    $auth = new Auth();
} catch (Exception $e) {
    // Database connection might be down
    error_log("Failed to initialize Auth: " . $e->getMessage());
    $auth = null;
}
?>