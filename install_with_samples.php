<?php
/**
 * Dataset Sharing and Collaboration Platform
 * Installation Script with Sample Data Option
 * 
 * This script sets up the database and creates the first admin user
 * Option to include working sample data for testing and demonstration
 */

// Prevent running if already installed
if (file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$message = '';
$messageType = '';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'dataset_platform',
    'username' => 'root',
    'password' => '1212'
];

if ($_POST) {
    if ($step == 1 && isset($_POST['test_db'])) {
        // Test database connection
        try {
            $pdo = new PDO("mysql:host={$dbConfig['host']}", $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $message = 'Database connection successful!';
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = 'Database connection failed: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($step == 2 && isset($_POST['create_db'])) {
        // Create database and tables
        $includeSampleData = isset($_POST['include_sample_data']);
        
        try {
            $pdo = new PDO("mysql:host={$dbConfig['host']}", $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Choose schema file based on user preference
            $schemaFile = $includeSampleData ? 'database/sample_data_schema.sql' : 'database/production_schema.sql';
            
            if (!file_exists($schemaFile)) {
                throw new Exception("Schema file not found: $schemaFile");
            }
            
            $schema = file_get_contents($schemaFile);
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $pdo->exec($statement);
                }
            }
            
            if ($includeSampleData) {
                $message = 'Database, tables, and sample data created successfully! You can now test with 8 sample datasets.';
            } else {
                $message = 'Database and tables created successfully! Ready for production use.';
            }
            $messageType = 'success';
            $step = 3;
        } catch(Exception $e) {
            $message = 'Database creation failed: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($step == 3 && isset($_POST['create_admin'])) {
        // Create admin user
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $messageType = 'danger';
        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } else {
            try {
                $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", $dbConfig['username'], $dbConfig['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if admin already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $message = 'An admin user already exists.';
                    $messageType = 'warning';
                } else {
                    // Create admin user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $stmt->execute([$name, $email, $hashedPassword]);
                    
                    $message = 'Admin user created successfully!';
                    $messageType = 'success';
                    $step = 4;
                }
            } catch(PDOException $e) {
                $message = 'Failed to create admin user: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($step == 4 && isset($_POST['finalize'])) {
        // Create uploads directory and lock file
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
        }
        
        if (!file_exists('config')) {
            mkdir('config', 0755, true);
        }
        
        // Create installation lock file
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        // Create .htaccess for uploads security
        $htaccess = "Options -Indexes\n";
        $htaccess .= "Order deny,allow\n";
        $htaccess .= "Deny from all\n";
        $htaccess .= "<Files ~ \"\\.(csv|json|txt|xlsx|xls)$\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Allow from all\n";
        $htaccess .= "</Files>\n";
        file_put_contents('uploads/.htaccess', $htaccess);
        
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Dataset Sharing Platform</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .install-header {
            background: #2563eb;
            color: white;
            padding: 2rem;
            text-align: center;
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
            margin: 0 10px;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: bold;
        }
        .step.active {
            background: #2563eb;
            color: white;
        }
        .step.completed {
            background: #10b981;
            color: white;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #e5e7eb;
            margin-top: 19px;
        }
        .step-line.completed {
            background: #10b981;
        }
        .sample-data-option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .sample-data-option.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="install-container">
                    <div class="install-header">
                        <h2><i class="bi bi-database me-2"></i>Dataset Sharing Platform</h2>
                        <p class="mb-0">Installation with Sample Data Option</p>
                    </div>
                    
                    <div class="install-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                            <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
                            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                            <div class="step-line <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
                            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
                            <div class="step-line <?php echo $step > 3 ? 'completed' : ''; ?>"></div>
                            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
                        </div>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?> me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <!-- Step 1: Database Connection -->
                            <h4>Step 1: Database Connection</h4>
                            <p class="text-muted">Test the database connection with your MySQL server.</p>
                            
                            <div class="mb-3">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" value="<?php echo $dbConfig['host']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" value="<?php echo $dbConfig['dbname']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo $dbConfig['username']; ?>" readonly>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="test_db" class="btn btn-primary">
                                    <i class="bi bi-database me-2"></i>Test Connection
                                </button>
                                <?php if ($messageType === 'success'): ?>
                                    <a href="?step=2" class="btn btn-success ms-2">
                                        <i class="bi bi-arrow-right me-2"></i>Next Step
                                    </a>
                                <?php endif; ?>
                            </form>
                            
                        <?php elseif ($step == 2): ?>
                            <!-- Step 2: Create Database -->
                            <h4>Step 2: Create Database & Choose Data Option</h4>
                            <p class="text-muted">Create the database and choose whether to include sample data.</p>
                            
                            <form method="POST">
                                <div class="sample-data-option">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_sample_data" name="include_sample_data" checked>
                                        <label class="form-check-label" for="include_sample_data">
                                            <strong>Include Sample Data</strong>
                                        </label>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Includes 8 realistic datasets with working CSV files:
                                        Student Performance, COVID-19 Data, House Prices, Iris Dataset, 
                                        Stock Market, Climate Data, Social Sentiment, and IoT Sensors.
                                        Perfect for testing and demonstration.
                                    </p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-database me-2"></i>
                                    This will create the database <strong><?php echo $dbConfig['dbname']; ?></strong> and all required tables.
                                </div>
                                
                                <button type="submit" name="create_db" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Create Database
                                </button>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            <!-- Step 3: Create Admin User -->
                            <h4>Step 3: Create Admin User</h4>
                            <p class="text-muted">Create the first administrator account for the platform.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="admin_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="admin_name" name="admin_name" 
                                           value="<?php echo htmlspecialchars($_POST['admin_name'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                           value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                           minlength="8" required>
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="8" required>
                                </div>
                                
                                <button type="submit" name="create_admin" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>Create Admin User
                                </button>
                            </form>
                            
                        <?php elseif ($step == 4): ?>
                            <!-- Step 4: Finalize Installation -->
                            <h4>Step 4: Finalize Installation</h4>
                            <p class="text-muted">Complete the installation and secure the system.</p>
                            
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Installation is almost complete! Click below to finalize.
                            </div>
                            
                            <div class="mb-3">
                                <h6>What will be done:</h6>
                                <ul>
                                    <li>Create uploads directory with security settings</li>
                                    <li>Generate installation lock file</li>
                                    <li>Set up file permissions</li>
                                    <li>Redirect to the platform</li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="finalize" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i>Complete Installation
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('admin_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                function validatePassword() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                password.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
            }
            
            // Sample data option styling
            const checkbox = document.getElementById('include_sample_data');
            const option = document.querySelector('.sample-data-option');
            
            if (checkbox && option) {
                function updateOptionStyle() {
                    if (checkbox.checked) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                }
                
                checkbox.addEventListener('change', updateOptionStyle);
                updateOptionStyle(); // Initial call
            }
        });
    </script>
</body>
</html>
