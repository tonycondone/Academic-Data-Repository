<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = SupabaseService::getConnection();
} catch(PDOException $e) {
    $pdo = null;
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
    $conditions[] = "(d.title ILIKE :search OR d.description ILIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($category) {
    $conditions[] = "d.category = :category";
    $params[':category'] = $category;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Sort options
$orderClause = match($sort) {
    'oldest' => 'ORDER BY d.upload_date ASC',
    'name' => 'ORDER BY d.title ASC',
    'popular' => 'ORDER BY d.download_count DESC',
    'rating' => 'ORDER BY avg_rating DESC',
    default => 'ORDER BY d.upload_date DESC'
};

if ($pdo) {
    $query = "SELECT * FROM dataset_overview $whereClause $orderClause LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $countQuery = "SELECT COUNT(*) FROM dataset_overview $whereClause";
    $stmt = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalDatasets = $stmt->fetchColumn();
    $totalPages = ceil($totalDatasets / $limit);
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category ORDER BY count DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $datasets = [];
    $totalDatasets = 0;
    $totalPages = 0;
    $categories = [];
}

// Page specific variables
$page_title = 'My Projects';
$page_description = 'Manage your dataset projects and collaborations';
$body_class = 'projects-page';

// Include header
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/projects.css">

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>My Projects</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Projects</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Projects Section -->
<section class="projects section">
  <div class="container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="page-title">My Projects</h1>
          <p class="page-subtitle">
            Manage your collaborative dataset projects
            <?php if ($totalDatasets > 0): ?>
              • <?php echo $totalDatasets; ?> project<?php echo $totalDatasets !== 1 ? 's' : ''; ?> found
            <?php endif; ?>
          </p>
        </div>
        <div class="col-md-4 text-end">
          <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty')): ?>
          <a href="create-project.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>New Project
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
      <form method="GET" action="" class="row g-3">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" class="form-control" name="search" 
                   placeholder="Search projects..." 
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>
        </div>
        
        <div class="col-md-3">
          <select class="form-select" name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['count']; ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-3">
          <select class="form-select" name="sort">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
          </select>
        </div>
        
        <div class="col-md-2">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">
              <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="projects.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-1"></i>Clear
            </a>
          </div>
        </div>
      </form>
    </div>

    <!-- Projects List -->
    <?php if (empty($datasets)): ?>
      <div class="empty-state text-center py-5">
        <i class="bi bi-folder2-open display-1 text-muted"></i>
        <h3 class="mt-3">No Projects Found</h3>
        <p class="text-muted">
          <?php if ($search || $category): ?>
            No projects match your current filters. Try adjusting your search criteria.
          <?php elseif (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty')): ?>
            You haven't created any projects yet. <a href="create-project.php">Create your first project</a> to get started.
          <?php else: ?>
            You haven't joined any projects yet. Ask your faculty to invite you to a project.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($datasets as $dataset): ?>
        <div class="col-md-6 col-lg-4 mb-4">
          <div class="project-card h-100">
            <div class="project-header">
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="project-title">
                    <?php echo htmlspecialchars($dataset['title']); ?>
                  </a>
                  <div class="project-meta">
                    by <?php echo htmlspecialchars($dataset['uploader_name']); ?>
                    • <?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?>
                  </div>
                  <?php if ($dataset['description']): ?>
                  <div class="project-description">
                    <?php echo htmlspecialchars(substr($dataset['description'], 0, 150)); ?>
                    <?php if (strlen($dataset['description']) > 150): ?>...<?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="d-flex flex-column gap-2 align-items-end">
                  <span class="badge bg-primary">
                    <?php echo htmlspecialchars($dataset['category']); ?>
                  </span>
                  <?php if ($dataset['review_count'] > 0): ?>
                  <div class="rating-display">
                    <?php 
                    $rating = round($dataset['avg_rating']);
                    for ($i = 1; $i <= 5; $i++): 
                    ?>
                      <i class="bi bi-star<?php echo $i <= $rating ? '-fill' : ''; ?> text-warning"></i>
                    <?php endfor; ?>
                    <small class="text-muted ms-1">(<?php echo $dataset['review_count']; ?>)</small>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <div class="project-stats">
              <div class="stat-group">
                <div class="stat-item">
                  <i class="bi bi-download"></i>
                  <span><?php echo $dataset['download_count']; ?> downloads</span>
                </div>
                <div class="stat-item">
                  <i class="bi bi-file-earmark"></i>
                  <span><?php echo strtoupper(pathinfo($dataset['filename'], PATHINFO_EXTENSION)); ?></span>
                </div>
                <div class="stat-item">
                  <i class="bi bi-hdd"></i>
                  <span><?php echo number_format($dataset['file_size'] / 1024, 1); ?> KB</span>
                </div>
              </div>
              
              <div class="project-actions">
                <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-eye me-1"></i>View
                </a>
                <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary btn-sm">
                  <i class="bi bi-download me-1"></i>Download
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav aria-label="Projects pagination" class="mt-4">
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
</section>

<?php
// Include footer
include 'includes/footer.php';
?>
