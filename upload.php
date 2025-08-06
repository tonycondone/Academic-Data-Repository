<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Include PhpSpreadsheet autoloader
if (file_exists('autoload.php')) {
    require_once 'autoload.php';
}

// Database connection
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';
$messageType = '';

// Handle dataset upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dataset'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($category)) {
        $message = 'Title and category are required.';
        $messageType = 'danger';
    } elseif (empty($_FILES['dataset_file']['name'])) {
        $message = 'Please select a file to upload.';
        $messageType = 'danger';
    } else {
        $file = $_FILES['dataset_file'];
        $allowedTypes = ['csv', 'xlsx', 'xls', 'json', 'txt', 'pdf'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            $message = 'Only CSV, Excel, JSON, TXT, and PDF files are allowed.';
            $messageType = 'danger';
        } elseif ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
            $message = 'File size must be less than 50MB.';
            $messageType = 'danger';
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Skip Excel to CSV conversion - keep original file
                $finalPath = $filePath;
                $finalFilename = $filename;
                
                // Insert into database
                try {
                    $stmt = $pdo->prepare("INSERT INTO datasets (title, filename, category, description, file_path, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $title,
                        $finalFilename,
                        $category,
                        $description,
                        $finalPath,
                        filesize($finalPath),
                        $_SESSION['user_id']
                    ]);
                    
                    if (empty($message)) {
                        $message = 'Dataset uploaded successfully!';
                        $messageType = 'success';
                    }
                    
                    // Clear form
                    $_POST = [];
                } catch(PDOException $e) {
                    $message = 'Database error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            } else {
                $message = 'Failed to upload file.';
                $messageType = 'danger';
            }
        }
    }
}

// Get upload statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_datasets FROM datasets WHERE is_active = 1");
$totalDatasets = $stmt->fetch(PDO::FETCH_ASSOC)['total_datasets'];

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $pdo->query("SELECT SUM(download_count) as total_downloads FROM datasets");
$totalDownloads = $stmt->fetch(PDO::FETCH_ASSOC)['total_downloads'] ?? 0;

// Get user's upload count
$stmt = $pdo->prepare("SELECT COUNT(*) as my_uploads FROM datasets WHERE uploaded_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$myUploads = $stmt->fetch(PDO::FETCH_ASSOC)['my_uploads'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dataset - Dataset Sharing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        /* Custom styles for upload page */
        .upload-hero {
            background: linear-gradient(135deg, #2487ce 0%, #2487ce 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .upload-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .upload-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
        }

        .upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 15px;
            padding: 60px 20px;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafafa;
            position: relative;
            cursor: pointer;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: #2487ce;
            background: #f0f8ff;
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            box-shadow: 0 5px 20px rgba(36, 135, 206, 0.2);
        }

        .upload-icon {
            font-size: 80px;
            color: #2487ce;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .upload-button {
            background: #2487ce;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(36, 135, 206, 0.3);
        }

        .upload-button:hover {
            background: #1a6fb5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(36, 135, 206, 0.4);
        }

        .upload-button i {
            font-size: 20px;
        }

        .file-info-display {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
            align-items: center;
            gap: 15px;
        }

        .file-info-display.active {
            display: flex;
        }

        .file-icon {
            font-size: 40px;
            color: #2487ce;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .file-size {
            color: #666;
            font-size: 14px;
        }

        .remove-file {
            color: #dc3545;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .remove-file:hover {
            transform: scale(1.2);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .stats-number {
            font-size: 36px;
            font-weight: 700;
            color: #2487ce;
            margin-bottom: 10px;
        }

        .stats-label {
            color: #666;
            font-size: 16px;
        }

        .guidelines-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .guidelines-box h5 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .guideline-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #555;
        }

        .guideline-item i {
            color: #28a745;
            margin-right: 10px;
            font-size: 18px;
        }

        .supported-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .type-badge {
            background: #e9ecef;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .type-badge i {
            color: #2487ce;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .upload-box {
                padding: 20px;
            }
            
            .upload-area {
                padding: 40px 15px;
            }
            
            .upload-icon {
                font-size: 60px;
            }
            
            .upload-button {
                padding: 12px 30px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-database me-2"></i>Dataset Platform
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php">Browse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">Upload</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Upload Hero Section -->
    <section class="upload-hero">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-3">Upload Dataset</h1>
                <p class="lead">Share your datasets with the community</p>
            </div>
        </div>
    </section>

    <!-- Main Upload Section -->
    <section class="py-5">
        <div class="container upload-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>-fill me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Upload Form -->
                <div class="col-lg-8 mb-4">
                    <div class="upload-box">
                        <h3 class="mb-4">
                            <i class="bi bi-cloud-upload me-2"></i>Upload New Dataset
                        </h3>
                        
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="form-label fw-semibold">Dataset Title *</label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                       placeholder="Enter a descriptive title for your dataset"
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-4">
                                <label for="category" class="form-label fw-semibold">Category *</label>
                                <select class="form-select form-select-lg" id="category" name="category" required>
                                    <option value="">Select a category...</option>
                                    <option value="AI" <?php echo ($_POST['category'] ?? '') === 'AI' ? 'selected' : ''; ?>>AI</option>
                                    <option value="AGRICULTURE" <?php echo ($_POST['category'] ?? '') === 'AGRICULTURE' ? 'selected' : ''; ?>>AGRICULTURE</option>
                                    <option value="Business" <?php echo ($_POST['category'] ?? '') === 'Business' ? 'selected' : ''; ?>>Business</option>
                                    <option value="Education" <?php echo ($_POST['category'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                                    <option value="Environment" <?php echo ($_POST['category'] ?? '') === 'Environment' ? 'selected' : ''; ?>>Environment</option>
                                    <option value="Finance" <?php echo ($_POST['category'] ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Health" <?php echo ($_POST['category'] ?? '') === 'Health' ? 'selected' : ''; ?>>Health</option>
                                    <option value="ICT" <?php echo ($_POST['category'] ?? '') === 'ICT' ? 'selected' : ''; ?>>ICT</option>
                                    <option value="Social Sciences" <?php echo ($_POST['category'] ?? '') === 'Social Sciences' ? 'selected' : ''; ?>>Social Sciences</option>
                                    <option value="TRANSPORT" <?php echo ($_POST['category'] ?? '') === 'TRANSPORT' ? 'selected' : ''; ?>>TRANSPORT</option>
                                </select>
                            </div>
                            
                            <!-- File Upload Area -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Select File *</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                    <h4 class="mb-3">Drag & Drop your file here</h4>
                                    <p class="text-muted mb-4">or</p>
                                    <button type="button" class="upload-button" onclick="document.getElementById('fileInput').click()">
                                        <i class="bi bi-folder2-open"></i>
                                        Browse Files
                                    </button>
                                    <input type="file" id="fileInput" name="dataset_file" class="d-none" required 
                                           accept=".csv,.xlsx,.xls,.json,.txt,.pdf">
                                    
                                    <div class="supported-types">
                                        <span class="type-badge"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</span>
                                        <span class="type-badge"><i class="bi bi-file-earmark-excel"></i> Excel</span>
                                        <span class="type-badge"><i class="bi bi-file-earmark-code"></i> JSON</span>
                                        <span class="type-badge"><i class="bi bi-file-earmark-text"></i> TXT</span>
                                        <span class="type-badge"><i class="bi bi-file-earmark-pdf"></i> PDF</span>
                                    </div>
                                </div>
                                
                                <div class="file-info-display" id="fileInfo">
                                    <i class="bi bi-file-earmark file-icon"></i>
                                    <div class="file-details">
                                        <div class="file-name" id="fileName"></div>
                                        <div class="file-size" id="fileSize"></div>
                                    </div>
                                    <i class="bi bi-x-circle remove-file" id="removeFile"></i>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Provide a detailed description of your dataset..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <button type="submit" name="upload_dataset" class="btn btn-primary btn-lg px-5">
                                    <i class="bi bi-upload me-2"></i>Upload Dataset
                                </button>
                                <a href="admin.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Admin
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalDatasets; ?></div>
                                <div class="stats-label">Total Datasets</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $myUploads; ?></div>
                                <div class="stats-label">My Uploads</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalUsers; ?></div>
                                <div class="stats-label">Active Users</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo number_format($totalDownloads); ?></div>
                                <div class="stats-label">Downloads</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guidelines -->
                    <div class="guidelines-box">
                        <h5><i class="bi bi-info-circle me-2"></i>Upload Guidelines</h5>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Maximum file size: 50MB</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Use descriptive titles</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Select appropriate category</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Provide detailed descriptions</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Ensure data quality</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Remove sensitive information</span>
                        </div>
                        <div class="guideline-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Excel files auto-convert to CSV</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 Dataset Sharing Platform. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFile = document.getElementById('removeFile');

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                displayFileInfo(this.files[0]);
            }
        });

        // Remove file
        removeFile.addEventListener('click', function() {
            fileInput.value = '';
            fileInfo.classList.remove('active');
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                displayFileInfo(files[0]);
            }
        });

        // Display file information
        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.add('active');
            
            // Update file icon based on type
            const fileIcon = document.querySelector('.file-icon');
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (['xlsx', 'xls', 'csv'].includes(extension)) {
                fileIcon.className = 'bi bi-file-earmark-spreadsheet file-icon';
            } else if (['pdf'].includes(extension)) {
                fileIcon.className = 'bi bi-file-earmark-pdf file-icon';
            } else if (['json'].includes(extension)) {
                fileIcon.className = 'bi bi-file-earmark-code file-icon';
            } else if (['txt'].includes(extension)) {
                fileIcon.className = 'bi bi-file-earmark-text file-icon';
            } else {
                fileIcon.className = 'bi bi-file-earmark file-icon';
            }
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value;
            const category = document.getElementById('category').value;
            const file = fileInput.files[0];
            
            if (!title || !category || !file) {
                e.preventDefault();
                alert('Please fill in all required fields and select a file');
                return;
            }
            
            // Check file size (50MB)
            if (file.size > 50 * 1024 * 1024) {
                e.preventDefault();
                alert('File size exceeds maximum allowed size of 50MB');
                return;
            }
        });
    </script>
</body>
</html>
