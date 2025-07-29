<?php
session_start();

// Enforce authentication: only logged-in users or admins can preview datasets
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get dataset ID
$datasetId = (int)($_GET['id'] ?? 0);

if (!$datasetId) {
    header('Location: index.php');
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get dataset details
    $stmt = $pdo->prepare("SELECT * FROM dataset_overview WHERE id = ?");
    $stmt->execute([$datasetId]);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dataset) {
        header('Location: index.php');
        exit;
    }
    
    // Get file path from datasets table
    $stmt = $pdo->prepare("SELECT file_path FROM datasets WHERE id = ?");
    $stmt->execute([$datasetId]);
    $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fileData || !file_exists($fileData['file_path'])) {
        $previewData = null;
        $canPreview = false;
    } else {
        $filePath = $fileData['file_path'];
        $fileExtension = strtolower(pathinfo($dataset['filename'], PATHINFO_EXTENSION));
        
        // Preview data based on file type
        $previewData = [];
        $canPreview = false;
        
        if ($fileExtension === 'csv' && file_exists($filePath)) {
            $canPreview = true;
            $handle = fopen($filePath, 'r');
            $rowCount = 0;
            $maxRows = 50;
            
            while (($data = fgetcsv($handle, 1000, ",", '"', "\\")) !== FALSE && $rowCount < $maxRows) {
                $previewData[] = $data;
                $rowCount++;
            }
            fclose($handle);
            
        } elseif ($fileExtension === 'json' && file_exists($filePath)) {
            $canPreview = true;
            $jsonContent = file_get_contents($filePath);
            $jsonData = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $previewData = $jsonData;
            }
            
        } elseif ($fileExtension === 'txt' && file_exists($filePath)) {
            $canPreview = true;
            $previewData = substr(file_get_contents($filePath), 0, 2000);
        }
    }
    
} catch(PDOException $e) {
    header('Location: index.php');
    exit;
}

// Page specific variables
$page_title = 'Preview: ' . $dataset['title'];
$page_description = 'Preview dataset: ' . $dataset['title'];
$body_class = 'preview-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Dataset Preview</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="browse.php">Browse</a></li>
            <li class="breadcrumb-item active" aria-current="page">Preview</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Dataset Preview -->
<section class="preview section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <!-- Dataset Header -->
        <div class="dataset-header">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h1 class="dataset-title">
                <i class="bi bi-<?php 
                    $ext = strtolower(pathinfo($dataset['filename'], PATHINFO_EXTENSION));
                    echo match($ext) {
                        'csv' => 'file-earmark-spreadsheet',
                        'xlsx', 'xls' => 'file-earmark-excel',
                        'json' => 'file-earmark-code',
                        'txt' => 'file-earmark-text',
                        default => 'file-earmark'
                    };
                ?> me-2"></i>
                <?php echo htmlspecialchars($dataset['title']); ?>
              </h1>
              
              <div class="dataset-meta">
                <span class="meta-item">
                  <i class="bi bi-person me-1"></i>
                  by <?php echo htmlspecialchars($dataset['uploader_name']); ?>
                </span>
                <span class="meta-item">
                  <i class="bi bi-tag me-1"></i>
                  <?php echo htmlspecialchars($dataset['category']); ?>
                </span>
                <span class="meta-item">
                  <i class="bi bi-hdd me-1"></i>
                  <?php echo number_format($dataset['file_size'] / 1024, 1); ?> KB
                </span>
                <span class="meta-item">
                  <i class="bi bi-calendar me-1"></i>
                  <?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?>
                </span>
              </div>
            </div>
            
            <div class="col-md-4 text-end">
              <div class="action-buttons">
                <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary">
                  <i class="bi bi-download me-2"></i>Download
                </a>
                <a href="browse.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-2"></i>Back
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Dataset Description -->
        <?php if ($dataset['description']): ?>
        <div class="dataset-description">
          <h5>Description</h5>
          <p><?php echo nl2br(htmlspecialchars($dataset['description'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Dataset Statistics -->
        <div class="dataset-stats">
          <div class="row">
            <div class="col-md-3">
              <div class="stat-card">
                <div class="stat-number"><?php echo $dataset['download_count']; ?></div>
                <div class="stat-label">Downloads</div>
              </div>
            </div>
            <?php if ($dataset['review_count'] > 0): ?>
            <div class="col-md-3">
              <div class="stat-card">
                <div class="stat-number"><?php echo number_format($dataset['avg_rating'], 1); ?></div>
                <div class="stat-label">Average Rating</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card">
                <div class="stat-number"><?php echo $dataset['review_count']; ?></div>
                <div class="stat-label">Reviews</div>
              </div>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
              <div class="stat-card">
                <div class="stat-number"><?php echo strtoupper(pathinfo($dataset['filename'], PATHINFO_EXTENSION)); ?></div>
                <div class="stat-label">Format</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Content -->
        <div class="preview-content">
          <?php if ($canPreview): ?>
            <?php 
            $fileExtension = strtolower(pathinfo($dataset['filename'], PATHINFO_EXTENSION));
            if ($fileExtension === 'csv' && !empty($previewData)): 
            ?>
              <!-- CSV Preview -->
              <h5>Data Preview (First 50 rows)</h5>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead class="table-dark">
                    <tr>
                      <th style="width: 50px;">#</th>
                      <?php foreach ($previewData[0] as $index => $header): ?>
                        <th>Column <?php echo $index + 1; ?></th>
                      <?php endforeach; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($previewData as $rowIndex => $row): ?>
                      <tr>
                        <td><strong><?php echo $rowIndex + 1; ?></strong></td>
                        <?php foreach ($row as $cell): ?>
                          <td title="<?php echo htmlspecialchars($cell); ?>">
                            <?php echo htmlspecialchars(substr($cell, 0, 100)) . (strlen($cell) > 100 ? '...' : ''); ?>
                          </td>
                        <?php endforeach; ?>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
            <?php elseif ($fileExtension === 'json'): ?>
              <h5>JSON Preview</h5>
              <div class="json-preview">
                <pre><code><?php echo htmlspecialchars(json_encode($previewData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></code></pre>
              </div>
              
            <?php elseif ($fileExtension === 'txt'): ?>
              <h5>Text Preview (First 2000 characters)</h5>
              <div class="text-preview">
                <pre><?php echo htmlspecialchars($previewData); ?></pre>
                <?php if (strlen(file_get_contents($fileData['file_path'])) > 2000): ?>
                  <div class="mt-2 text-muted">
                    <em>... (truncated, download full file to see complete content)</em>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
          <?php else: ?>
            <!-- No Preview Available -->
            <div class="no-preview text-center py-5">
              <i class="bi bi-eye-slash display-1 text-muted"></i>
              <h4 class="mt-3">Preview Not Available</h4>
              <p class="text-muted">
                Preview is not supported for this file type.<br>
                Download the file to view its contents.
              </p>
              <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary mt-3">
                <i class="bi bi-download me-2"></i>Download Dataset
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Preview page specific styles */
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

.dataset-header {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.dataset-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 1rem;
}

.dataset-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  color: #666;
}

.meta-item {
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.dataset-description {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.dataset-description h5 {
  color: #333;
  margin-bottom: 1rem;
}

.dataset-stats {
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  height: 100%;
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

.preview-content {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.preview-content h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.table {
  font-size: 0.875rem;
}

.table th {
  background: #f8f9fa;
  border-top: none;
  font-weight: 600;
}

.json-preview {
  background: #1a202c;
  color: #e2e8f0;
  padding: 1.5rem;
  border-radius: 8px;
  max-height: 500px;
  overflow: auto;
}

.json-preview pre {
  margin: 0;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.875rem;
  line-height: 1.6;
}

.text-preview {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  max-height: 500px;
  overflow: auto;
}

.text-preview pre {
  margin: 0;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  white-space: pre-wrap;
}

.no-preview {
  color: #999;
}

@media (max-width: 768px) {
  .dataset-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .action-buttons {
    flex-direction: column;
    width: 100%;
  }
  
  .dataset-title {
    font-size: 1.5rem;
  }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
