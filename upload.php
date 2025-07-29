<?php
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/excel_converter.php';

// Initialize session
startSecureSession();

// Require admin access
requireAdmin();

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
if ($_POST && isset($_POST['upload_dataset'])) {
    checkCSRFToken();
    
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
        $allowedTypes = ['csv', 'xlsx', 'xls', 'json', 'txt'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            $message = 'Only CSV, Excel, JSON, and TXT files are allowed.';
            $messageType = 'danger';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $message = 'File size must be less than 10MB.';
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
                // Convert Excel to CSV if needed
                $finalPath = $filePath;
                $finalFilename = $filename;
                
                if (in_array($fileExtension, ['xlsx', 'xls'])) {
                    // Use ExcelConverter for proper conversion
                    $csvFilename = pathinfo($filename, PATHINFO_FILENAME) . '.csv';
                    $csvPath = $uploadDir . $csvFilename;
                    
                    $conversionResult = ExcelConverter::convertToCSV($filePath, $csvPath);
                    
                    if ($conversionResult['success']) {
                        $finalPath = $csvPath;
                        $finalFilename = $csvFilename;
                        
                        // Add conversion note to message
                        if (isset($conversionResult['partial']) && $conversionResult['partial']) {
                            $message = 'Dataset uploaded. Note: ' . $conversionResult['message'];
                            $messageType = 'warning';
                        }
                    } else {
                        // Conversion failed, use original Excel file
                        $message = 'Excel conversion failed: ' . $conversionResult['message'] . '. Original Excel file saved.';
                        $messageType = 'warning';
                    }
                }
                
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
                    
                    $message = 'Dataset uploaded successfully!';
                    $messageType = 'success';
                    
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
$stmt = $pdo->query("SELECT COUNT(*) as total_datasets FROM datasets");
$totalDatasets = $stmt->fetch(PDO::FETCH_ASSOC)['total_datasets'];

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $pdo->query("SELECT SUM(download_count) as total_downloads FROM datasets");
$totalDownloads = $stmt->fetch(PDO::FETCH_ASSOC)['total_downloads'] ?? 0;

// Page specific variables
$page_title = 'Upload Dataset';
$page_description = 'Upload a new dataset to the platform';
$body_class = 'upload-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Upload Dataset</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="admin.php">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Upload</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Upload Section -->
<section class="upload section">
  <div class="container">
    <div class="row">
      <!-- Upload Form -->
      <div class="col-lg-8">
        <div class="upload-form">
          <h4>
            <i class="bi bi-upload me-2"></i>Upload New Dataset
          </h4>
          
          <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
              <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
              <?php echo htmlspecialchars($message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <form method="POST" enctype="multipart/form-data">
            <?php echo csrfTokenField(); ?>
            
            <div class="mb-3">
              <label for="title" class="form-label">Dataset Title *</label>
              <input type="text" class="form-control" id="title" name="title" 
                     value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
              <div class="form-text">Choose a descriptive title for your dataset</div>
            </div>
            
            <div class="mb-3">
              <label for="category" class="form-label">Category *</label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="Machine Learning" <?php echo ($_POST['category'] ?? '') === 'Machine Learning' ? 'selected' : ''; ?>>Machine Learning</option>
                <option value="Business" <?php echo ($_POST['category'] ?? '') === 'Business' ? 'selected' : ''; ?>>Business</option>
                <option value="Health" <?php echo ($_POST['category'] ?? '') === 'Health' ? 'selected' : ''; ?>>Health</option>
                <option value="Education" <?php echo ($_POST['category'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                <option value="Social Sciences" <?php echo ($_POST['category'] ?? '') === 'Social Sciences' ? 'selected' : ''; ?>>Social Sciences</option>
                <option value="Environment" <?php echo ($_POST['category'] ?? '') === 'Environment' ? 'selected' : ''; ?>>Environment</option>
                <option value="Technology" <?php echo ($_POST['category'] ?? '') === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                <option value="Finance" <?php echo ($_POST['category'] ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="4" 
                        placeholder="Provide a detailed description of your dataset..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
              <div class="form-text">Describe what the dataset contains and its potential uses</div>
            </div>
            
            <div class="mb-4">
              <label for="dataset_file" class="form-label">Dataset File *</label>
              <div class="file-drop-zone" onclick="document.getElementById('dataset_file').click()">
                <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                <p class="mb-2">Click to select file or drag and drop</p>
                <small class="text-muted">Supported formats: CSV, Excel (.xlsx, .xls), JSON, TXT (Max: 10MB)</small>
              </div>
              <input type="file" class="form-control d-none" id="dataset_file" name="dataset_file" 
                     accept=".csv,.xlsx,.xls,.json,.txt" required>
              <div class="file-info" id="file-info"></div>
            </div>
            
            <div class="upload-actions">
              <button type="submit" name="upload_dataset" class="btn btn-primary">
                <i class="bi bi-upload me-2"></i>Upload Dataset
              </button>
              <a href="admin.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Admin
              </a>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Upload Info -->
      <div class="col-lg-4">
        <!-- Platform Stats -->
        <div class="upload-stats">
          <h5>Platform Statistics</h5>
          <div class="stat-item">
            <div class="stat-number"><?php echo $totalDatasets; ?></div>
            <div class="stat-label">Total Datasets</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Active Users</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $totalDownloads; ?></div>
            <div class="stat-label">Total Downloads</div>
          </div>
        </div>

        <!-- Upload Guidelines -->
        <div class="upload-guidelines">
          <h5>Upload Guidelines</h5>
          <ul class="guidelines-list">
            <li><i class="bi bi-check-circle text-success me-2"></i>Use descriptive, clear titles</li>
            <li><i class="bi bi-check-circle text-success me-2"></i>Select appropriate categories</li>
            <li><i class="bi bi-check-circle text-success me-2"></i>Provide detailed descriptions</li>
            <li><i class="bi bi-check-circle text-success me-2"></i>Ensure data quality and accuracy</li>
            <li><i class="bi bi-check-circle text-success me-2"></i>Remove sensitive information</li>
            <li><i class="bi bi-check-circle text-success me-2"></i>Use standard file formats</li>
          </ul>
        </div>

        <!-- Supported Formats -->
        <div class="supported-formats">
          <h5>Supported Formats</h5>
          <div class="format-list">
            <div class="format-item">
              <i class="bi bi-file-earmark-spreadsheet text-success"></i>
              <span>CSV Files</span>
            </div>
            <div class="format-item">
              <i class="bi bi-file-earmark-excel text-success"></i>
              <span>Excel Files (.xlsx, .xls)</span>
            </div>
            <div class="format-item">
              <i class="bi bi-file-earmark-code text-success"></i>
              <span>JSON Files</span>
            </div>
            <div class="format-item">
              <i class="bi bi-file-earmark-text text-success"></i>
              <span>Text Files</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Upload page specific styles */
.page-title {
  background: #f8f9fa;
  padding: 40px 0;
  margin-bottom: 40px;
}

.page-title h2 {
  margin-bottom: 10px;
  color: #333;
}

.breadcrumb {
  background: none;
  padding: 0;
  margin: 0;
}

.upload-form {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.upload-form h4 {
  color: #333;
  margin-bottom: 1.5rem;
}

.file-drop-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 10px;
  padding: 3rem 2rem;
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
  background: #f8f9fa;
}

.file-drop-zone:hover,
.file-drop-zone.dragover {
  border-color: #2563eb;
  background: #eff6ff;
}

.file-info {
  display: none;
  margin-top: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.upload-actions {
  display: flex;
  gap: 1rem;
}

.upload-stats {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.upload-stats h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.stat-item {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #eee;
}

.stat-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #2563eb;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
}

.upload-guidelines {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.upload-guidelines h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.guidelines-list {
  list-style: none;
  padding: 0;
}

.guidelines-list li {
  padding: 0.5rem 0;
  display: flex;
  align-items: center;
}

.supported-formats {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.supported-formats h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.format-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.format-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.format-item i {
  font-size: 1.25rem;
}

@media (max-width: 768px) {
  .upload-actions {
    flex-direction: column;
  }
}
</style>

<script>
// File upload handling
const fileInput = document.getElementById('dataset_file');
const fileInfo = document.getElementById('file-info');
const dropZone = document.querySelector('.file-drop-zone');

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        const file = this.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        
        fileInfo.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-file-earmark me-2 text-primary"></i>
                <div>
                    <strong>Selected File:</strong> ${file.name}<br>
                    <small class="text-muted">Size: ${fileSize} MB | Type: ${file.type || 'Unknown'}</small>
                </div>
            </div>
        `;
        fileInfo.style.display = 'block';
    }
});

// Drag and drop
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
