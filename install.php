<?php
/**
 * Installation Script for Dataset Sharing and Collaboration Platform
 * This script helps set up the database and initial configuration
 */

// Prevent running if already installed
if (file_exists('config/installed.lock')) {
    die('Installation already completed. Delete config/installed.lock to reinstall.');
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    switch ($step) {
        case 1:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? 'academic_collaboration';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($dbUser)) {
                $error = 'Database username is required.';
            } else {
                // Test database connection
                try {
                    // PostgreSQL connection
                    $pdo = new PDO("pgsql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Store database config in session
                    session_start();
                    $_SESSION['install_config'] = [
                        'db_host' => $dbHost,
                        'db_name' => $dbName,
                        'db_user' => $dbUser,
                        'db_pass' => $dbPass
                    ];
                    
                    header('Location: install.php?step=2');
                    exit;
                    
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 2:
            // Import database schema
            session_start();
            $config = $_SESSION['install_config'] ?? null;
            
            if (!$config) {
                header('Location: install.php?step=1');
                exit;
            }
            
            try {
                $pdo = new PDO("pgsql:host={$config['db_host']};dbname={$config['db_name']}", 
                              $config['db_user'], $config['db_pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read and execute schema
                $schema = file_get_contents('database/schema_postgres.sql');
                $statements = explode(';', $schema);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                header('Location: install.php?step=3');
                exit;
                
            } catch (Exception $e) {
                $error = 'Database setup failed: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Admin user creation
            session_start();
            $config = $_SESSION['install_config'] ?? null;
            
            if (!$config) {
                header('Location: install.php?step=1');
                exit;
            }
            
            $adminUsername = $_POST['admin_username'] ?? '';
            $adminEmail = $_POST['admin_email'] ?? '';
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminFirstName = $_POST['admin_first_name'] ?? '';
            $adminLastName = $_POST['admin_last_name'] ?? '';
            
            if (empty($adminUsername) || empty($adminEmail) || empty($adminPassword) || 
                empty($adminFirstName) || empty($adminLastName)) {
                $error = 'All fields are required.';
            } elseif (strlen($adminPassword) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                try {
                    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']}", 
                                  $config['db_user'], $config['db_pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Update default admin user
                    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, 
                                          first_name = ?, last_name = ? WHERE id = 1");
                    $stmt->execute([$adminUsername, $adminEmail, $passwordHash, $adminFirstName, $adminLastName]);
                    
                    header('Location: install.php?step=4');
                    exit;
                    
                } catch (Exception $e) {
                    $error = 'Admin user creation failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 4:
            // Final configuration
            session_start();
            $config = $_SESSION['install_config'] ?? null;
            
            if (!$config) {
                header('Location: install.php?step=1');
                exit;
            }
            
            $baseUrl = $_POST['base_url'] ?? 'http://localhost/dataset-platform/';
            $appName = $_POST['app_name'] ?? 'Dataset Sharing and Collaboration Platform';
            
            // Create config file
            $configContent = "<?php\n";
            $configContent .= "// Auto-generated configuration file\n";
            $configContent .= "// Generated on " . date('Y-m-d H:i:s') . "\n\n";
            $configContent .= "// Start session if not already started\n";
            $configContent .= "if (session_status() == PHP_SESSION_NONE) {\n";
            $configContent .= "    session_start();\n";
            $configContent .= "}\n\n";
            $configContent .= "// Error reporting (disable in production)\n";
            $configContent .= "error_reporting(E_ALL);\n";
            $configContent .= "ini_set('display_errors', 1);\n\n";
            $configContent .= "// Application settings\n";
            $configContent .= "define('APP_NAME', '{$appName}');\n";
            $configContent .= "define('APP_VERSION', '1.0.0');\n";
            $configContent .= "define('BASE_URL', '{$baseUrl}');\n";
            $configContent .= "define('ROOT_PATH', dirname(__DIR__) . '/');\n\n";
            $configContent .= "// Database settings\n";
            $configContent .= "define('DB_HOST', '{$config['db_host']}');\n";
            $configContent .= "define('DB_NAME', '{$config['db_name']}');\n";
            $configContent .= "define('DB_USER', '{$config['db_user']}');\n";
            $configContent .= "define('DB_PASS', '{$config['db_pass']}');\n\n";
            $configContent .= "// File upload settings\n";
            $configContent .= "define('UPLOAD_PATH', ROOT_PATH . 'uploads/');\n";
            $configContent .= "define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB\n";
            $configContent .= "define('ALLOWED_FILE_TYPES', [\n";
            $configContent .= "    'csv', 'xlsx', 'xls', 'json', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'doc', 'docx'\n";
            $configContent .= "]);\n\n";
            $configContent .= "// Security settings\n";
            $configContent .= "define('CSRF_TOKEN_NAME', 'csrf_token');\n";
            $configContent .= "define('SESSION_LIFETIME', 3600 * 8); // 8 hours\n";
            $configContent .= "define('PASSWORD_MIN_LENGTH', 8);\n\n";
            $configContent .= "// Pagination settings\n";
            $configContent .= "define('ITEMS_PER_PAGE', 20);\n";
            $configContent .= "define('FILES_PER_PAGE', 15);\n\n";
            $configContent .= "// Version control settings\n";
            $configContent .= "define('MAX_VERSIONS_PER_FILE', 100);\n";
            $configContent .= "define('VERSION_STORAGE_PATH', ROOT_PATH . 'versions/');\n\n";
            $configContent .= "// Email settings (for notifications)\n";
            $configContent .= "define('SMTP_HOST', 'localhost');\n";
            $configContent .= "define('SMTP_PORT', 587);\n";
            $configContent .= "define('SMTP_USERNAME', '');\n";
            $configContent .= "define('SMTP_PASSWORD', '');\n";
            $configContent .= "define('FROM_EMAIL', 'noreply@university.edu');\n";
            $configContent .= "define('FROM_NAME', 'Dataset Sharing Platform');\n\n";
            $configContent .= "// Timezone\n";
            $configContent .= "date_default_timezone_set('UTC');\n\n";
            $configContent .= "// Include required files\n";
            $configContent .= "require_once ROOT_PATH . 'config/database.php';\n";
            $configContent .= "require_once ROOT_PATH . 'includes/functions.php';\n";
            $configContent .= "require_once ROOT_PATH . 'includes/auth.php';\n\n";
            $configContent .= "// Create upload directories if they don't exist\n";
            $configContent .= "if (!file_exists(UPLOAD_PATH)) {\n";
            $configContent .= "    mkdir(UPLOAD_PATH, 0755, true);\n";
            $configContent .= "}\n";
            $configContent .= "if (!file_exists(VERSION_STORAGE_PATH)) {\n";
            $configContent .= "    mkdir(VERSION_STORAGE_PATH, 0755, true);\n";
            $configContent .= "}\n\n";
            $configContent .= "// CSRF Token generation\n";
            $configContent .= "function generateCSRFToken() {\n";
            $configContent .= "    if (!isset(\$_SESSION[CSRF_TOKEN_NAME])) {\n";
            $configContent .= "        \$_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));\n";
            $configContent .= "    }\n";
            $configContent .= "    return \$_SESSION[CSRF_TOKEN_NAME];\n";
            $configContent .= "}\n\n";
            $configContent .= "// Verify CSRF Token\n";
            $configContent .= "function verifyCSRFToken(\$token) {\n";
            $configContent .= "    return isset(\$_SESSION[CSRF_TOKEN_NAME]) && hash_equals(\$_SESSION[CSRF_TOKEN_NAME], \$token);\n";
            $configContent .= "}\n\n";
            $configContent .= "// Auto-logout on session timeout\n";
            $configContent .= "if (isset(\$_SESSION['last_activity']) && (time() - \$_SESSION['last_activity'] > SESSION_LIFETIME)) {\n";
            $configContent .= "    session_unset();\n";
            $configContent .= "    session_destroy();\n";
            $configContent .= "    header('Location: login.php?timeout=1');\n";
            $configContent .= "    exit();\n";
            $configContent .= "}\n";
            $configContent .= "\$_SESSION['last_activity'] = time();\n";
            $configContent .= "?>";
            
            // Write config file
            if (file_put_contents('config/config.php', $configContent)) {
                // Create installation lock file
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                // Clear session
                unset($_SESSION['install_config']);
                
                header('Location: install.php?step=5');
                exit;
            } else {
                $error = 'Failed to create configuration file. Check file permissions.';
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Dataset Sharing and Collaboration Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .install-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .install-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .install-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .install-body {
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: 600;
            position: relative;
        }
        
        .step.active {
            background: #4f46e5;
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .step.pending {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
        }
        
        .step.completed:not(:last-child)::after {
            background: #10b981;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating input,
        .form-floating select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .form-floating input:focus,
        .form-floating select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        .btn-install {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .info-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-box h5 {
            color: #4f46e5;
            margin-bottom: 1rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="install-container">
                    <div class="install-header">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h2>Platform Installation</h2>
                        <p>Dataset Sharing and Collaboration Platform Setup</p>
                    </div>
                    
                    <div class="install-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</div>
                            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
                            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending'; ?>">3</div>
                            <div class="step <?php echo $step >= 4 ? ($step > 4 ? 'completed' : 'active') : 'pending'; ?>">4</div>
                            <div class="step <?php echo $step >= 5 ? 'completed' : 'pending'; ?>">5</div>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <!-- Step 1: Database Configuration -->
                            <div class="info-box">
                                <h5><i class="fas fa-database me-2"></i>Database Configuration</h5>
                                <p>Enter your MySQL database connection details. The installer will create the database if it doesn't exist.</p>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="db_host" name="db_host" 
                                           placeholder="Database Host" value="localhost" required>
                                    <label for="db_host">Database Host</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="db_name" name="db_name" 
                                           placeholder="Database Name" value="academic_collaboration" required>
                                    <label for="db_name">Database Name</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="db_user" name="db_user" 
                                           placeholder="Database Username" required>
                                    <label for="db_user">Database Username</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                           placeholder="Database Password">
                                    <label for="db_pass">Database Password</label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-install">
                                        <i class="fas fa-arrow-right me-2"></i>Test Connection & Continue
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 2): ?>
                            <!-- Step 2: Database Setup -->
                            <div class="info-box">
                                <h5><i class="fas fa-cogs me-2"></i>Database Setup</h5>
                                <p>The installer will now create the necessary database tables and initial data.</p>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-install">
                                        <i class="fas fa-database me-2"></i>Create Database Tables
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            <!-- Step 3: Admin User -->
                            <div class="info-box">
                                <h5><i class="fas fa-user-shield me-2"></i>Administrator Account</h5>
                                <p>Create your administrator account. This account will have full access to the platform.</p>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="admin_first_name" name="admin_first_name" 
                                                   placeholder="First Name" required>
                                            <label for="admin_first_name">First Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="admin_last_name" name="admin_last_name" 
                                                   placeholder="Last Name" required>
                                            <label for="admin_last_name">Last Name</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                           placeholder="Username" required>
                                    <label for="admin_username">Username</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                           placeholder="Email Address" required>
                                    <label for="admin_email">Email Address</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                           placeholder="Password" required minlength="8">
                                    <label for="admin_password">Password</label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-install">
                                        <i class="fas fa-user-plus me-2"></i>Create Administrator
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 4): ?>
                            <!-- Step 4: Final Configuration -->
                            <div class="info-box">
                                <h5><i class="fas fa-cog me-2"></i>Final Configuration</h5>
                                <p>Configure the final settings for your platform installation.</p>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="app_name" name="app_name" 
                                           placeholder="Application Name" value="Dataset Sharing and Collaboration Platform" required>
                                    <label for="app_name">Application Name</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="url" class="form-control" id="base_url" name="base_url" 
                                           placeholder="Base URL" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/'; ?>" required>
                                    <label for="base_url">Base URL</label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-install">
                                        <i class="fas fa-check me-2"></i>Complete Installation
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 5): ?>
                            <!-- Step 5: Installation Complete -->
                            <div class="text-center">
                                <div class="success-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3>Installation Complete!</h3>
                                <p class="mb-4">Your Dataset Sharing and Collaboration Platform has been successfully installed.</p>
                                
                                <div class="info-box text-start">
                                    <h5><i class="fas fa-info-circle me-2"></i>Next Steps</h5>
                                    <ul>
                                        <li>Delete the <code>install.php</code> file for security</li>
                                        <li>Configure your web server (Apache/Nginx)</li>
                                        <li>Set up SSL certificate for production</li>
                                        <li>Configure email settings in <code>config/config.php</code></li>
                                        <li>Review and adjust file upload limits</li>
                                    </ul>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="login.php" class="btn btn-primary btn-install">
                                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login Page
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>