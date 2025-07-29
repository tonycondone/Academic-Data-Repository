<?php
session_start();

// Database configuration
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

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(title LIKE :search OR description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($category) {
    $conditions[] = "category = :category";
    $params[':category'] = $category;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Sort options
$orderClause = match($sort) {
    'oldest' => 'ORDER BY upload_date ASC',
    'name' => 'ORDER BY title ASC',
    'popular' => 'ORDER BY download_count DESC',
    'rating' => 'ORDER BY avg_rating DESC',
    default => 'ORDER BY upload_date DESC'
};

// Get datasets
$query = "SELECT * FROM dataset_overview $whereClause $orderClause";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM datasets ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Page specific variables
$page_title = 'Browse Datasets';
$page_description = 'Browse and search through available datasets on our platform';
$body_class = 'browse-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Browse Datasets</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Browse</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="datasets section">
  <div class="container">
    <div class="row">
      <!-- Sidebar with Filters -->
      <div class="col-lg-3">
        <div class="sidebar">
          <div class="widget">
            <h5 class="widget-title"><i class="bi bi-funnel me-2"></i>Categories</h5>
            
            <a href="?search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" 
               class="filter-option <?php echo empty($category) ? 'active' : ''; ?>">
                <i class="bi bi-grid-3x3-gap me-2"></i>All Categories
            </a>
            
            <?php foreach ($categories as $cat): ?>
            <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($cat); ?>&sort=<?php echo urlencode($sort); ?>" 
               class="filter-option <?php echo $category === $cat ? 'active' : ''; ?>">
                <i class="bi bi-folder me-2"></i><?php echo htmlspecialchars($cat); ?>
            </a>
            <?php endforeach; ?>
            
            <?php if ($search || $category): ?>
            <hr>
            <a href="browse.php" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-x-circle me-1"></i>Reset Filters
            </a>
            <?php endif; ?>
          </div>

          <!-- Search Widget -->
          <div class="widget mt-4">
            <h5 class="widget-title"><i class="bi bi-search me-2"></i>Search</h5>
            <form method="GET">
              <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
              <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
              <div class="input-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search datasets..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Main Content -->
      <div class="col-lg-9">
        <!-- Results Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 class="mb-1">
              <?php if ($search): ?>
                Search Results for "<?php echo htmlspecialchars($search); ?>"
              <?php elseif ($category): ?>
                <?php echo htmlspecialchars($category); ?> Datasets
              <?php else: ?>
                All Datasets
              <?php endif; ?>
            </h4>
            <p class="text-muted mb-0"><?php echo count($datasets); ?> datasets found</p>
          </div>
          
          <form method="GET" class="d-flex align-items-center">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <label class="me-2 small text-muted">Sort by:</label>
            <select name="sort" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
              <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
              <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
              <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
              <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Downloaded</option>
              <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
            </select>
          </form>
        </div>
        
        <!-- Dataset Cards -->
        <?php if (empty($datasets)): ?>
          <div class="empty-state text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h5 class="mt-3">No datasets found</h5>
            <p class="text-muted">Try adjusting your search criteria or browse all categories.</p>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin.php" class="btn btn-primary mt-3">
              <i class="bi bi-plus-circle me-2"></i>Upload Dataset
            </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="row">
            <?php foreach ($datasets as $dataset): ?>
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="dataset-card h-100">
                <div class="card-header">
                  <h5 class="card-title mb-1"><?php echo htmlspecialchars($dataset['title']); ?></h5>
                  <span class="badge bg-primary">
                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($dataset['category']); ?>
                  </span>
                </div>
                
                <div class="card-body">
                  <?php if ($dataset['description']): ?>
                  <p class="card-text text-muted small">
                    <?php echo htmlspecialchars(substr($dataset['description'], 0, 150)) . (strlen($dataset['description']) > 150 ? '...' : ''); ?>
                  </p>
                  <?php endif; ?>
                  
                  <div class="dataset-meta">
                    <div class="d-flex justify-content-between text-muted small mb-2">
                      <span>
                        <i class="bi bi-person me-1"></i>
                        <?php echo htmlspecialchars($dataset['uploader_name'] ?? 'Unknown'); ?>
                      </span>
                      <span>
                        <i class="bi bi-download me-1"></i>
                        <?php echo $dataset['download_count'] ?? 0; ?> downloads
                      </span>
                    </div>
                    
                    <?php if (isset($dataset['review_count']) && $dataset['review_count'] > 0): ?>
                    <div class="rating mb-3">
                      <span class="stars">
                        <?php 
                        $rating = round($dataset['avg_rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                          <i class="bi bi-star<?php echo $i <= $rating ? '-fill' : ''; ?> text-warning"></i>
                        <?php endfor; ?>
                      </span>
                      <small class="text-muted ms-1">
                        (<?php echo $dataset['review_count']; ?> reviews)
                      </small>
                    </div>
                    <?php endif; ?>
                  </div>
                  
                  <div class="card-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <!-- Authenticated users can preview and download -->
                      <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye me-1"></i>Preview
                      </a>
                      <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Download
                      </a>
                      <a href="review.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-star me-1"></i>Rate
                      </a>
                    <?php else: ?>
                      <!-- Non-authenticated users must login -->
                      <a href="login.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-lock me-1"></i>Login to Preview
                      </a>
                      <a href="login.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-lock me-1"></i>Login to Download
                      </a>
                      <small class="text-muted mt-2 d-block">
                        <i class="bi bi-info-circle me-1"></i>Create an account to access datasets
                      </small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<style>
/* Page specific styles */
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

.sidebar {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.widget {
  margin-bottom: 30px;
}

.widget-title {
  font-size: 1.1rem;
  margin-bottom: 15px;
  color: #333;
  font-weight: 600;
}

.filter-option {
  display: block;
  padding: 8px 12px;
  margin-bottom: 5px;
  border-radius: 5px;
  text-decoration: none;
  color: #666;
  transition: all 0.3s;
}

.filter-option:hover {
  background: #f8f9fa;
  color: #2563eb;
  text-decoration: none;
}

.filter-option.active {
  background: #2563eb;
  color: white;
}

.dataset-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0,0,0,0.05);
  transition: transform 0.3s, box-shadow 0.3s;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.dataset-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 30px rgba(0,0,0,0.1);
}

.dataset-card .card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: 15px 20px;
  border-radius: 10px 10px 0 0;
}

.dataset-card .card-title {
  font-size: 1.1rem;
  margin: 0;
  color: #333;
}

.dataset-card .card-body {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.dataset-meta {
  margin-top: auto;
}

.card-actions {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.empty-state {
  color: #999;
}

.empty-state i {
  opacity: 0.3;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
