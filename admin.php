<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Include PhpSpreadsheet autoloader
if (file_exists('autoload.php')) {
    require_once 'autoload.php';
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = SupabaseService::getConnection();
} catch(PDOException $e) {
    $pdo = null;
}

$message = '';
$messageType = '';

// Handle dataset upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dataset'])) {
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
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'json', 'txt'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Basic extension check
        if (!in_array($fileExtension, $allowedExtensions)) {
            $message = 'Only CSV, Excel, JSON, and TXT files are allowed.';
            $messageType = 'danger';
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $message = 'File size must be less than 10MB.';
            $messageType = 'danger';
        } else {
            // Validate MIME type for better security
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimeTypes = [
                'text/plain', 
                'text/csv', 
                'application/json', 
                'application/vnd.ms-excel', 
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream' // Some CSVs/Excel might show up as octet-stream
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes) && strpos($mimeType, 'text/') !== 0) {
                $message = 'Invalid file type. Please upload a valid dataset file.';
                $messageType = 'danger';
            } else {
                // Generate unique filename
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $keyPath = date('Y/m/') . $filename;
            $contentTypes = [
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls' => 'application/vnd.ms-excel',
                'json' => 'application/json',
                'txt' => 'text/plain'
            ];
            $contentType = $contentTypes[$fileExtension] ?? 'application/octet-stream';

            // Upload to Supabase Storage if configured
            $storage = new SupabaseStorage();
            $objectPath = null;
            if ($storage->isConfigured()) {
                $objectPath = $storage->upload($file['tmp_name'], $keyPath, $contentType);
            }

            if ($objectPath && $pdo) {
                $finalPath = $objectPath;
                $finalFilename = $filename;
                try {
                    $stmt = $pdo->prepare("INSERT INTO datasets (title, filename, category, description, file_path, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $title,
                        $finalFilename,
                        $category,
                        $description,
                        $finalPath,
                        (int)$file['size'],
                        $_SESSION['user_id']
                    ]);
                    $message = 'Dataset uploaded successfully!';
                    $messageType = 'success';
                    $_POST = [];
                } catch(PDOException $e) {
                    $message = 'Database error: ' . $e->getMessage();
                    $messageType = 'danger';
                    Logger::error("Database error during dataset upload", ['error' => $e->getMessage(), 'title' => $title]);
                }
            } else {
                $message = 'Failed to upload file.';
                $messageType = 'danger';
                Logger::error("File upload failed to storage", ['title' => $title, 'filename' => $filename]);
            }
        }
    }
}

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM dataset_overview ORDER BY upload_date DESC LIMIT 10");
    $recentUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT COUNT(*) as total_datasets FROM datasets");
    $totalDatasets = $stmt->fetch(PDO::FETCH_ASSOC)['total_datasets'];
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    $stmt = $pdo->query("SELECT SUM(download_count) as total_downloads FROM datasets");
    $totalDownloads = $stmt->fetch(PDO::FETCH_ASSOC)['total_downloads'] ?? 0;
} else {
    $recentUploads = [];
    $totalDatasets = 0;
    $totalUsers = 0;
    $totalDownloads = 0;
    if (!$message) {
        $message = 'Database not configured';
        $messageType = 'warning';
    }
}

// Page specific variables
$page_title = 'Admin Panel';
$page_description = 'Admin panel for managing datasets and users';
$body_class = 'admin-page';

// Include header
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/admin.css">

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Admin Panel</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Admin</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Statistics Section -->
<section class="stats section">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
        <div class="stats-card">
          <div class="stats-icon datasets">
            <i class="bi bi-database"></i>
          </div>
          <h3 class="stats-number"><?php echo $totalDatasets; ?></h3>
          <p class="stats-label">Total Datasets</p>
        </div>
      </div>
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="stats-card">
          <div class="stats-icon users">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="stats-number"><?php echo $totalUsers; ?></h3>
          <p class="stats-label">Registered Users</p>
        </div>
      </div>
      <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
        <div class="stats-card">
          <div class="stats-icon downloads">
            <i class="bi bi-download"></i>
          </div>
          <h3 class="stats-number"><?php echo $totalDownloads; ?></h3>
          <p class="stats-label">Total Downloads</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="admin-content section">
  <div class="container">
    <div class="row">
      <!-- Upload Form -->
      <div class="col-lg-8">
        <div class="upload-card">
          <h4 class="mb-4">
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
            </div>
            
            <div class="mb-3">
              <label for="category" class="form-label">Category *</label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Select Category</option>
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
            
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
            
            <button type="submit" name="upload_dataset" class="btn btn-primary">
              <i class="bi bi-upload me-2"></i>Upload Dataset
            </button>
          </form>
        </div>
      </div>
      
      <!-- Recent Uploads -->
      <div class="col-lg-4">
        <div class="recent-uploads">
          <h5 class="mb-4">
            <i class="bi bi-clock-history me-2"></i>Recent Uploads
          </h5>
          
          <?php if (empty($recentUploads)): ?>
            <p class="text-muted text-center py-3">No datasets uploaded yet.</p>
          <?php else: ?>
            <?php foreach ($recentUploads as $upload): ?>
            <div class="upload-item">
              <h6 class="mb-1">
                <a href="preview.php?id=<?php echo $upload['id']; ?>" class="text-decoration-none">
                  <?php echo htmlspecialchars($upload['title']); ?>
                </a>
              </h6>
              <small class="text-muted">
                <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($upload['category']); ?> • 
                <?php echo date('M j, Y', strtotime($upload['upload_date'])); ?>
              </small>
              <div class="mt-1">
                <small class="text-success">
                  <i class="bi bi-download me-1"></i><?php echo $upload['download_count']; ?> downloads
                </small>
                <?php if ($upload['review_count'] > 0): ?>
                <small class="text-warning ms-2">
                  <i class="bi bi-star-fill me-1"></i><?php echo number_format($upload['avg_rating'], 1); ?> (<?php echo $upload['review_count']; ?>)
                </small>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

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
            <strong>Selected File:</strong> ${file.name}<br>
            <strong>Size:</strong> ${fileSize} MB<br>
            <strong>Type:</strong> ${file.type || 'Unknown'}
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
