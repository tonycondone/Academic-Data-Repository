<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = null;

try {
    $pdo = SupabaseService::getConnection();
} catch(PDOException $e) {
    // Database unavailable
}

$user = $_SESSION;
$isAdmin = $user['role'] === 'admin';

// Initialize variables
$totalDatasets = 0;
$userReviews = 0;
$userDownloads = 0;
$recentDatasets = [];
$userRecentReviews = [];

// Get user statistics
if ($pdo) {
    try {
        // Get total datasets
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM datasets WHERE is_active = TRUE");
        $totalDatasets = $stmt->fetch()['count'];
        
        // Get user's reviews count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $userReviews = $stmt->fetch()['count'];
        
        // Get user's downloads count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM downloads WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $userDownloads = $stmt->fetch()['count'];
        
        // Get recent datasets
        $stmt = $pdo->query("SELECT * FROM dataset_overview ORDER BY upload_date DESC LIMIT 5");
        $recentDatasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user's recent reviews
        $stmt = $pdo->prepare("
            SELECT r.*, d.title as dataset_title 
            FROM reviews r 
            JOIN datasets d ON r.dataset_id = d.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user['user_id']]);
        $userRecentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        // Keep defaults
    }
}

// Page specific variables
$page_title = 'Dashboard';
$page_description = 'Your personal dashboard for dataset sharing and collaboration';
$body_class = 'dashboard-page';

// Include header
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<!-- Welcome Section -->
<section class="welcome-section section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="welcome-title">
          Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
        </h1>
        <p class="welcome-subtitle mb-0">
          <?php echo ucfirst($user['role']); ?> • Dataset Sharing Platform
        </p>
      </div>
      <div class="col-md-4 text-end">
        <?php if ($isAdmin): ?>
        <a href="admin.php" class="btn btn-light btn-lg">
          <i class="bi bi-gear me-2"></i>Admin Panel
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Statistics Section -->
<section class="stats section">
  <div class="container">
    <div class="row">
      <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="100">
        <div class="stats-card">
          <div class="stats-icon datasets">
            <i class="bi bi-database"></i>
          </div>
          <h3 class="stats-number"><?php echo $totalDatasets; ?></h3>
          <p class="stats-label">Total Datasets</p>
        </div>
      </div>
      
      <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="stats-card">
          <div class="stats-icon reviews">
            <i class="bi bi-star"></i>
          </div>
          <h3 class="stats-number"><?php echo $userReviews; ?></h3>
          <p class="stats-label">Your Reviews</p>
        </div>
      </div>
      
      <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="300">
        <div class="stats-card">
          <div class="stats-icon downloads">
            <i class="bi bi-download"></i>
          </div>
          <h3 class="stats-number"><?php echo $userDownloads; ?></h3>
          <p class="stats-label">Your Downloads</p>
        </div>
      </div>
      
      <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="400">
        <div class="stats-card">
          <div class="stats-icon activity">
            <i class="bi bi-graph-up"></i>
          </div>
          <h3 class="stats-number"><?php echo count($userRecentReviews); ?></h3>
          <p class="stats-label">Recent Activity</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="dashboard-content section">
  <div class="container">
    <div class="row">
      <!-- Recent Datasets -->
      <div class="col-md-8">
        <div class="section-card">
          <div class="section-header">
            <div class="d-flex justify-content-between align-items-center">
              <h2 class="section-title">Recent Datasets</h2>
              <a href="browse.php" class="btn btn-outline-primary btn-sm">Browse All</a>
            </div>
          </div>
          
          <?php if (empty($recentDatasets)): ?>
            <div class="text-center py-5">
              <i class="bi bi-database display-1 text-muted mb-3"></i>
              <p class="text-muted">No datasets found.</p>
            </div>
          <?php else: ?>
            <?php foreach ($recentDatasets as $dataset): ?>
            <div class="dataset-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="dataset-title">
                    <?php echo htmlspecialchars($dataset['title']); ?>
                  </a>
                  <div class="dataset-meta">
                    by <?php echo htmlspecialchars($dataset['uploader_name']); ?>
                    • <?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?>
                    • <?php echo htmlspecialchars($dataset['category']); ?>
                  </div>
                  <?php if ($dataset['description']): ?>
                  <div class="dataset-description">
                    <?php echo htmlspecialchars(substr($dataset['description'], 0, 150)) . (strlen($dataset['description']) > 150 ? '...' : ''); ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="dataset-actions">
                  <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-download"></i>
                  </a>
                </div>
              </div>
              <div class="dataset-stats">
                <span class="dataset-stat">
                  <i class="bi bi-download me-1"></i><?php echo $dataset['download_count']; ?> downloads
                </span>
                <?php if ($dataset['review_count'] > 0): ?>
                <span class="dataset-stat">
                  <i class="bi bi-star-fill me-1 text-warning"></i><?php echo number_format($dataset['avg_rating'], 1); ?> (<?php echo $dataset['review_count']; ?> reviews)
                </span>
                <?php endif; ?>
                <span class="dataset-stat">
                  <i class="bi bi-file-earmark me-1"></i><?php echo strtoupper(pathinfo($dataset['filename'], PATHINFO_EXTENSION)); ?>
                </span>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="section-card mb-4">
          <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
          </div>
          <div class="quick-actions">
            <a href="browse.php" class="btn btn-outline-primary w-100 mb-2">
              <i class="bi bi-search me-2"></i>Browse Datasets
            </a>
            <?php if ($isAdmin): ?>
            <a href="admin.php" class="btn btn-primary w-100 mb-2">
              <i class="bi bi-upload me-2"></i>Admin Upload
            </a>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-outline-secondary w-100">
              <i class="bi bi-person me-2"></i>Edit Profile
            </a>
          </div>
        </div>
        
        <!-- Recent Reviews -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">Your Recent Reviews</h2>
          </div>
          
          <?php if (empty($userRecentReviews)): ?>
            <div class="text-center py-4">
              <i class="bi bi-star display-4 text-muted mb-2"></i>
              <p class="text-muted small">No reviews yet</p>
              <a href="browse.php" class="btn btn-sm btn-outline-primary">Browse & Review</a>
            </div>
          <?php else: ?>
            <?php foreach ($userRecentReviews as $review): ?>
            <div class="review-item">
              <div class="review-dataset">
                <a href="preview.php?id=<?php echo $review['dataset_id']; ?>">
                  <?php echo htmlspecialchars($review['dataset_title']); ?>
                </a>
              </div>
              <div class="review-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?> text-warning"></i>
                <?php endfor; ?>
              </div>
              <?php if ($review['comment']): ?>
              <div class="review-comment">
                <?php echo htmlspecialchars(substr($review['comment'], 0, 100)) . (strlen($review['comment']) > 100 ? '...' : ''); ?>
              </div>
              <?php endif; ?>
              <div class="review-date">
                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>
