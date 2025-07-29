hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

.dataset-card .card-body {
    padding: 1.5rem;
}

.dataset-card .category-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* File type colors */
.file-type-csv { background-color: #28a745; color: white; }
.file-type-excel { background-color: #1a73e8; color: white; }
.file-type-json { background-color: #ff6b6b; color: white; }
.file-type-pdf { background-color: #dc3545; color: white; }

/* Search and filter sidebar */
.filter-sidebar {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    position: sticky;
    top: 20px;
}

/* Upload area */
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    transition: all 0.3s ease;
}

.upload-area.dragover {
    border-color: var(--primary-color);
    background-color: rgba(74, 144, 226, 0.1);
}

/* Rating stars */
.rating-stars {
    color: #ffc107;
}

.rating-stars .fa-star {
    cursor: pointer;
    transition: color 0.2s;
}

.rating-stars .fa-star:hover,
.rating-stars .fa-star.active {
    color: #ff9800;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .filter-sidebar {
        position: relative;
        margin-bottom: 20px;
    }
    
    .dataset-card {
        margin-bottom: 20px;
    }
}
```

## 4.4 Core Module Implementation

### 4.4.1 User Authentication Module

The authentication module provides secure user management with role-based access control:

**Login Implementation (login.php):**
```php
<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Academic Data Repository</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Login</h2>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <p class="text-center mb-0">
                            Don't have an account? <a href="register.php">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

**Registration Implementation (register.php):**
```php
<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($username, $email, $password, $role);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!-- Registration form HTML similar to login -->
```

### 4.4.2 File Management Module

The file management module handles dataset uploads, storage, and retrieval:

**Upload Handler (upload.php):**
```php
<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/file_handler.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->canUpload()) {
    die('You do not have permission to upload files.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileHandler = new FileHandler();
    
    // Get form data
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Handle file upload
    if (isset($_FILES['dataset']) && $_FILES['dataset']['error'] === UPLOAD_ERR_OK) {
        $result = $fileHandler->uploadDataset(
            $_FILES['dataset'],
            $title,
            $description,
            $category_id,
            $_SESSION['user_id'],
            $is_public
        );
        
        if ($result['success']) {
            $success = 'Dataset uploaded successfully!';
            // Redirect to dataset page
            header('Location: ' . SITE_URL . '/dataset.php?id=' . $result['dataset_id']);
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}
?>

<!-- Upload form with drag-and-drop interface -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-upload"></i> Upload Dataset</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                            <h4>Drag and drop your file here</h4>
                            <p>or</p>
                            <label for="dataset" class="btn btn-primary">
                                Choose File
                                <input type="file" id="dataset" name="dataset" 
                                       accept=".csv,.xlsx,.xls,.json,.pdf,.txt" 
                                       style="display: none;" required>
                            </label>
                            <p class="mt-3 text-muted">
                                Supported formats: CSV, Excel, JSON, PDF, TXT (Max: 50MB)
                            </p>
                        </div>
                        
                        <div id="fileInfo" style="display: none;" class="mt-3">
                            <div class="alert alert-info">
                                <strong>Selected file:</strong> <span id="fileName"></span>
                                <br>
                                <strong>Size:</strong> <span id="fileSize"></span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="mb-3">
                                <label for="title" class="form-label">Dataset Title</label>
                                <input type="text" class="form-control" id="title" 
                                       name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" 
                                          name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php
                                    // Fetch categories from database
                                    $db = Database::getInstance()->getConnection();
                                    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
                                    while ($category = $stmt->fetch()) {
                                        echo '<option value="' . $category['id'] . '">' . 
                                             htmlspecialchars($category['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_public" name="is_public" checked>
                                    <label class="form-check-label" for="is_public">
                                        Make this dataset publicly accessible
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload"></i> Upload Dataset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Drag and drop functionality
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('dataset');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        displayFileInfo(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        displayFileInfo(e.target.files[0]);
    }
});

function displayFileInfo(file) {
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileInfo.style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
```

### 4.4.3 Search and Filter Module

The search module provides comprehensive dataset discovery capabilities:

**Search Implementation (search.php):**
```php
<?php
require_once 'config/config.php';
require_once 'includes/search_handler.php';

$searchHandler = new SearchHandler();

// Get search parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';
$page = max(1, intval($_GET['page'] ?? 1));

// Perform search
$results = $searchHandler->search($query, [
    'category' => $category,
    'sort' => $sort,
    'page' => $page,
    'per_page' => ITEMS_PER_PAGE
]);

// Extract results
$datasets = $results['datasets'];
$total = $results['total'];
$totalPages = ceil($total / ITEMS_PER_PAGE);
?>

<div class="container mt-4">
    <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-md-3">
            <div class="filter-sidebar">
                <h5>Filters</h5>
                <hr>
                
                <form method="GET" action="">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php
                            $categories = $searchHandler->getCategories();
                            foreach ($categories as $cat) {
                                $selected = $category == $cat['id'] ? 'selected' : '';
                                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . 
                                     htmlspecialchars($cat['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>
                                Relevance
                            </option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>
                                Newest First
                            </option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>
                                Most Popular
                            </option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>
                                Highest Rated
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>
            </div>
        </div>
        
        <!-- Search Results -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Search Results</h3>
                <p class="text-muted mb-0">
                    Found <?php echo $total; ?> datasets
                    <?php if ($query): ?>
                    for "<?php echo htmlspecialchars($query); ?>"
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (empty($datasets)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No datasets found matching your criteria.
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($datasets as $dataset): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card dataset-card">
                        <div class="card-body">
                            <span class="category-badge file-type-<?php echo strtolower($dataset['category_name']); ?>">
                                <?php echo htmlspecialchars($dataset['category_name']); ?>
                            </span>
                            
                            <h5 class="card-title">
                                <a href="dataset.php?id=<?php echo $dataset['id']; ?>">
                                    <?php echo htmlspecialchars($dataset['title']); ?>
                                </a>
                            </h5>
                            
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($dataset['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($dataset['uploader']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-download"></i> <?php echo $dataset['download_count']; ?>
                                </small>
                            </div>
                            
                            <?php if ($dataset['average_rating']): ?>
                            <div class="rating-stars mt-2">
                                <?php
                                $rating = round($dataset['average_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <small>(<?php echo $dataset['rating_count']; ?>)</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Search results pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
```

### 4.4.4 Version Control Module

The version control system tracks changes and enables collaborative dataset management:

**Version Control Implementation (includes/version_control.php):**
```php
<?php
class VersionControl {
    private $db;
    private $uploadPath;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadPath = UPLOAD_PATH . 'versions/';
        
        // Ensure versions directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    public function createVersion($datasetId, $filePath, $description, $userId) {
        // Get current version number
        $stmt = $this->db->prepare("
            SELECT MAX(version_number) as max_version 
            FROM dataset_versions 
            WHERE dataset_id = :dataset_id
        ");
        $stmt->execute([':dataset_id' => $datasetId]);
        $result = $stmt->fetch();
        $newVersion = ($result['max_version'] ?? 0) + 1;
        
        // Calculate file checksum
        $checksum = hash_file('sha256', $filePath);
        $fileSize = filesize($filePath);
        
        // Copy file to versions directory
        $versionFileName = $datasetId . '_v' . $newVersion . '_' . time() . '_' . basename($filePath);
        $versionPath = $this->uploadPath . $versionFileName;
        
        if (!copy($filePath, $versionPath)) {
            throw new Exception('Failed to create version copy');
        }
        
        // Insert version record
        $stmt = $this->db->prepare("
            INSERT INTO dataset_versions 
            (dataset_id, version_number, file_path, file_size, checksum, description, created_by) 
            VALUES 
            (:dataset_id, :version_number, :file_path, :file_size, :checksum, :description, :created_by)
        ");
        
        $stmt->execute([
            ':dataset_id' => $datasetId,
            ':version_number' => $newVersion,
            ':file_path' => $versionPath,
            ':file_size' => $fileSize,
            ':checksum' => $checksum,
            ':description' => $description,
            ':created_by' => $userId
        ]);
        
        return [
            'success' => true,
            'version_number' => $newVersion,
            'checksum' => $checksum
        ];
    }
    
    public function getVersionHistory($datasetId) {
        $stmt = $this->db->prepare("
            SELECT v.*, u.username 
            FROM dataset_versions v
            JOIN users u ON v.created_by = u.id
            WHERE v.dataset_id = :dataset_id
            ORDER BY v.version_number DESC
        ");
        
        $stmt->execute([':dataset_id' => $datasetId]);
        return $stmt->fetchAll();
    }
    
    public function rollbackToVersion($datasetId, $versionNumber, $userId) {
        // Get version details
        $stmt = $this->db->prepare("
            SELECT * FROM dataset_versions 
            WHERE dataset_id = :dataset_id AND version_number = :version_number
        ");
        
        $stmt->execute([
            ':dataset_id' => $datasetId,
            ':version_number' => $versionNumber
        ]);
        
        $version = $stmt->fetch();
        if (!$version) {
            return ['success' => false, 'message' => 'Version not found'];
        }
        
        // Create new version from rollback
        $description = "Rollback to version " . $versionNumber;
        return $this->createVersion($datasetId, $version['file_path'], $description, $userId);
    }
    
    public function compareVersions($datasetId, $version1, $version2) {
        // Get both versions
        $stmt = $this->db->prepare("
            SELECT * FROM dataset_versions 
            WHERE dataset_id = :dataset_id 
            AND version_number IN (:v1, :v2)
        ");
        
        $stmt->execute([
            ':dataset_id' => $datasetId,
            ':v1' => $version1,
            ':v2' => $version2
        ]);
        
        $versions = $stmt->fetchAll();
        
        if (count($versions) !== 2) {
            return ['success' => false, 'message' => 'One or both versions not found'];
        }
        
        // Compare checksums
        if ($versions[0]['checksum'] === $versions[1]['checksum']) {
            return [
                'success' => true,
                'identical' => true,
                'message' => 'Versions are identical'
            ];
        }
        
        // Return comparison data
        return [
            'success' => true,
            'identical' => false,
            'version1' => $versions[0],
            'version2' => $versions[1],
            'size_difference' => abs($versions[0]['file_size'] - $versions[1]['file_size'])
        ];
    }
}
```

### 4.4.5 Review and Rating Module

The review system enables community-driven quality assessment:

**Review Handler (includes/review_handler.php):**
```php
<?php
class ReviewHandler {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function addReview($datasetId, $userId, $rating, $comment) {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Invalid rating value'];
        }
        
        // Check if user already reviewed this dataset
        $stmt = $this->db->prepare("
            SELECT id FROM reviews 
            WHERE dataset_id = :dataset_id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':dataset_id' => $datasetId,
            ':user_id' => $userId
        ]);
        
        if ($stmt->fetch()) {
            // Update existing review
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET rating = :rating, comment = :comment, updated_at = CURRENT_TIMESTAMP
                WHERE dataset_id = :dataset_id AND user_id = :user_id
            ");
        } else {
            // Insert new review
            $stmt = $this->db->prepare("
                INSERT INTO reviews (dataset_id, user_id, rating, comment)
                VALUES (:dataset_id, :user_id, :rating, :comment)
            ");
        }
        
        $stmt->execute([
            ':dataset_id' => $datasetId,
            ':user_id' => $userId,
            ':rating' => $rating,
            ':comment' => $comment
        ]);
        
        // Update dataset average rating
        $this->updateDatasetRating($datasetId);
        
        return ['success' => true, 'message' => 'Review submitted successfully'];
    }
    
    private function updateDatasetRating($datasetId) {
        $stmt = $this->db->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count
            FROM reviews
            WHERE dataset_id = :dataset_id
        ");
        
        $stmt->execute([':dataset_id' => $datasetId]);
        $result = $stmt->fetch();
        
        // Store calculated rating for performance
        $stmt = $this->db->prepare("
            UPDATE datasets 
            SET average_rating = :avg_rating, rating_count = :count
            WHERE id = :dataset_id
        ");
        
        $stmt->execute([
            ':avg_rating' => $result['avg_rating'],
            ':count' => $result['count'],
            ':dataset_id' => $datasetId
        ]);
    }
    
    public function getReviews($datasetId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.role
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.dataset_id = :dataset_id
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':dataset_id', $datasetId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
```

## 4.5 System Testing

### 4.5.1 Unit Testing

Unit tests were developed to verify individual component functionality:

**Authentication Tests:**
```php
class AuthTest extends PHPUnit\Framework\TestCase {
    private $auth;
    
    protected function setUp(): void {
        $this->auth = new Auth();
    }
    
    public function testUserRegistration() {
        $result = $this->auth->register(
            'testuser',
            'test@example.com',
            'SecurePass123!',
            'student'
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Registration successful', $result['message']);
    }
    
    public function testInvalidEmailRegistration() {
        $result = $this->auth->register(
            'testuser',
            'invalid-email',
            'SecurePass123!',
            'student'
        );
        
        $this->assertFalse($result['success']);
    }
    
    public function testPasswordHashing() {
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }
}
```

### 4.5.2 Integration Testing

Integration tests verified component interactions:

**File Upload Integration Test:**
```php
public function testCompleteFileUploadProcess() {
    // Login as test user
    $this->auth->login('test@example.com', 'password');
    
    // Prepare test file
    $testFile = [
        'name' => 'test_data.csv',
        'type' => 'text/csv',
        'tmp_name' => '/tmp/test_data.csv',
        'size' => 1024,
        'error' => UPLOAD_ERR_OK
    ];
    
    // Upload file
    $fileHandler = new FileHandler();
    $result = $fileHandler->uploadDataset(
        $testFile,
        'Test Dataset',
        'Test description',
        1, // Category ID
        1, // User ID
        true // Public
    );
