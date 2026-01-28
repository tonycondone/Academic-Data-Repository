<?php
/**
 * Authentication System
 * Academic Dataset Collaboration Platform
 */

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, email, password_hash, role, first_name, last_name, is_active 
                      FROM users WHERE (username = :username OR email = :username) AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
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
                'message' => 'Invalid username/email or password'
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
            
            // Check if username or email already exists
            if ($this->userExists($userData['username'], $userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }
            
            // Hash password
            $passwordHash = hashPassword($userData['password']);
            
            // Insert user
            $query = "INSERT INTO users (username, email, password_hash, role, first_name, last_name, department, student_id) 
                      VALUES (:username, :email, :password_hash, :role, :first_name, :last_name, :department, :student_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password_hash', $passwordHash);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':department', $userData['department']);
            $stmt->bindParam(':student_id', $userData['student_id']);
            
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
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $query = "SELECT id, username, email, role, first_name, last_name, department, student_id, created_at, last_login 
                      FROM users WHERE id = :user_id AND is_active = 1";
            
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
            $query = "SELECT password_hash FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
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
            $query = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password_hash', $newPasswordHash);
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
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
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
    
    private function userExists($username, $email) {
        try {
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
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
        $required = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
        
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
        if (!in_array($data['role'], ['admin', 'faculty', 'student'])) {
            return [
                'valid' => false,
                'message' => 'Invalid role selected'
            ];
        }
        
        // Validate username (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            return [
                'valid' => false,
                'message' => 'Username can only contain letters, numbers, and underscores'
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