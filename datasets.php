<?php
session_start();

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

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query conditions
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

// Get datasets with pagination
$query = "SELECT * FROM dataset_overview $whereClause $orderClause LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM dataset_overview $whereClause";
$stmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalDatasets = $stmt->fetchColumn();
$totalPages = ceil($totalDatasets / $limit);

// Get categories for filter
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category ORDER BY count DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page specific variables
$page_title = 'Browse Datasets';
$page_description = 'Explore and discover datasets for your research and projects';
$body_class = 'datasets-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Dataset Repository</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Datasets</li>
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
                <span><i class="bi bi-grid-3x3-gap me-2"></i>All Categories</span>
                <span class="filter-count"><?php echo $totalDatasets; ?></span>
            </a>
            
            <?php foreach ($categories as $cat): ?>
            <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($cat['category']); ?>&sort=<?php echo urlencode($sort); ?>" 
               class="filter-option <?php echo $category === $cat['category'] ? 'active' : ''; ?>">
                <span><i class="bi bi-folder me-2"></i><?php echo htmlspecialchars($cat['category']); ?></span>
                <span class="filter-count"><?php echo $cat['count']; ?></span>
            </a>
            <?php endforeach; ?>
            
            <?php if ($search || $category): ?>
            <hr>
            <a href="datasets.php" class="btn btn-outline-secondary btn-sm w-100">
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
            <p class="text-muted mb-0">
              Showing <?php echo count($datasets); ?> of <?php echo $totalDatasets; ?> datasets
              <?php if ($page > 1): ?>
                (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
              <?php endif; ?>
            </p>
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
            <p class="text-muted">
              <?php if ($search || $category): ?>
                Try adjusting your search criteria or browse all datasets.
              <?php else: ?>
                Be the first to upload a dataset!
              <?php endif; ?>
            </p>
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
                    
                    <div class="d-flex justify-content-between text-muted small mb-2">
                      <span>
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?>
                      </span>
                      <span>
                        <i class="bi bi-file-earmark me-1"></i>
                        <?php echo strtoupper(pathinfo($dataset['filename'], PATHINFO_EXTENSION)); ?>
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
                    <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-eye me-1"></i>Preview
                    </a>
                    <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-download me-1"></i>Download
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="review.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-star me-1"></i>Rate
                    </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <nav aria-label="Dataset pagination" class="mt-4">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>
              <?php endif; ?>
              
              <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                  <?php echo $i; ?>
                </a>
              </li>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </nav>
          <?php endif; ?>
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
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.filter-count {
  background: rgba(0,0,0,0.1);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.filter-option.active .filter-count {
  background: rgba(255,255,255,0.2);
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

.pagination {
  background: white;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0,0,0,0.05);
  padding: 10px;
}

.page-link {
  border: none;
  color: #666;
  font-weight: 600;
  padding: 8px 12px;
  margin: 0 2px;
  border-radius: 5px;
  transition: all 0.3s;
}

.page-link:hover {
  background: #2563eb;
  color: white;
}

.page-item.active .page-link {
  background: #2563eb;
  color: white;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
