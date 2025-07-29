<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$user = $_SESSION;
$isAdmin = $user['role'] === 'admin';

// Get user statistics
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
    $totalDatasets = 0;
    $userReviews = 0;
    $userDownloads = 0;
    $recentDatasets = [];
    $userRecentReviews = [];
}

// Page specific variables
$page_title = 'Dashboard';
$page_description = 'Your personal dashboard for dataset sharing and collaboration';
$body_class = 'dashboard-page';

// Include header
include 'includes/header.php';
?>

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
            <?php else: ?>
            <a href="upload.php" class="btn btn-primary w-100 mb-2">
              <i class="bi bi-upload me-2"></i>Upload Dataset
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

<style>
/* Dashboard specific styles */
.welcome-section {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  color: white;
  padding: 60px 0;
  margin-bottom: -40px;
}

.welcome-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.welcome-subtitle {
  opacity: 0.9;
  font-size: 1.1rem;
}

.stats-card {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: none;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  text-align: center;
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stats-icon {
  width: 60px;
  height: 60px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  margin: 0 auto 1rem;
}

.stats-icon.datasets {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.stats-icon.reviews {
  background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

.stats-icon.downloads {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stats-icon.activity {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stats-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.stats-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.section-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: none;
}

.section-header {
  padding: 1.5rem 1.5rem 0 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  padding-bottom: 1rem;
}

.dataset-item {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.3s ease;
}

.dataset-item:hover {
  background-color: #f9fafb;
}

.dataset-item:last-child {
  border-bottom: none;
}

.dataset-title {
  font-weight: 600;
  color: #1f2937;
  text-decoration: none;
  font-size: 1rem;
  display: block;
  margin-bottom: 0.25rem;
}

.dataset-title:hover {
  color: #2563eb;
}

.dataset-meta {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.dataset-description {
  font-size: 0.875rem;
  color: #4b5563;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.dataset-actions {
  display: flex;
  gap: 0.25rem;
}

.dataset-stats {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.dataset-stat {
  font-size: 0.75rem;
  color: #6b7280;
}

.quick-actions {
  padding: 0 1.5rem 1.5rem;
}

.review-item {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
}

.review-item:last-child {
  border-bottom: none;
}

.review-dataset {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.review-dataset a {
  color: #1f2937;
  text-decoration: none;
  font-size: 0.875rem;
}

.review-dataset a:hover {
  color: #2563eb;
}

.review-rating {
  margin-bottom: 0.5rem;
}

.review-comment {
  font-size: 0.875rem;
  color: #4b5563;
  margin-bottom: 0.25rem;
  line-height: 1.4;
}

.review-date {
  font-size: 0.75rem;
  color: #9ca3af;
}

@media (max-width: 768px) {
  .welcome-title {
    font-size: 1.5rem;
  }
  
  .dataset-actions {
    margin-top: 0.5rem;
  }
  
  .dataset-stats {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
