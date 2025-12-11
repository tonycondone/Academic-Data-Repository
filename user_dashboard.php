<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database is loaded via config
$db = new Database();

try {
    $pdo = $db->getConnection();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Get user statistics
$stats = [];

// User's reviews count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['my_reviews'] = $stmt->fetch()['total'];

// User's downloads (we'll simulate this with a random number for demo)
$stats['my_downloads'] = rand(15, 50);

// Favorite category (most reviewed category)
$stmt = $pdo->prepare("
    SELECT d.category, COUNT(*) as count 
    FROM reviews r 
    JOIN datasets d ON r.dataset_id = d.id 
    WHERE r.user_id = ? 
    GROUP BY d.category 
    ORDER BY count DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$fav_category = $stmt->fetch();
$stats['favorite_category'] = $fav_category ? $fav_category['category'] : 'None';

// Average rating given
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE user_id = ?");
$stmt->execute([$user_id]);
$avg_rating = $stmt->fetch()['avg_rating'];
$stats['avg_rating'] = $avg_rating ? round($avg_rating, 1) : 0;

// Recent datasets (popular ones)
$stmt = $pdo->query("SELECT * FROM datasets ORDER BY download_count DESC LIMIT 6");
$recommended_datasets = $stmt->fetchAll();

// User's recent reviews
$stmt = $pdo->prepare("
    SELECT r.*, d.title as dataset_title, d.category 
    FROM reviews r 
    JOIN datasets d ON r.dataset_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.timestamp DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$my_reviews = $stmt->fetchAll();

// Popular categories
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category ORDER BY count DESC LIMIT 5");
$popular_categories = $stmt->fetchAll();

// Recent activity (latest datasets)
$stmt = $pdo->query("SELECT * FROM datasets ORDER BY upload_date DESC LIMIT 5");
$recent_activity = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>User Dashboard - DataShare Platform</title>
  <meta name="description" content="Personal dashboard for managing your dataset activities and discoveries">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .dashboard-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: transform 0.2s;
    }
    
    .dashboard-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card {
      text-align: center;
      padding: 2rem 1rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px;
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-weight: 500;
      opacity: 0.9;
    }
    
    .stat-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.8;
    }
    
    .dataset-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
      height: 100%;
    }
    
    .dataset-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .dataset-header {
      background: linear-gradient(135deg, #2563eb, #3b82f6);
      color: white;
      padding: 1rem;
    }
    
    .rating-stars {
      color: #fbbf24;
    }
    
    .category-badge {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
    }
    
    .activity-item {
      border-left: 3px solid #2563eb;
      padding-left: 1rem;
      margin-bottom: 1rem;
    }
    
    .welcome-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px;
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #2563eb;"></i>
        <h1 class="sitename">My Dashboard</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="browse.php">Browse Datasets</a></li>
          <?php if ($is_admin): ?>
          <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
          <?php endif; ?>
          <li><a href="#dashboard" class="active">My Dashboard</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <div class="navbar-nav ms-auto">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="color: white;">
            <i class="bi bi-person-circle me-1"></i>
            <?php echo htmlspecialchars($_SESSION['name']); ?>
          </a>
          <ul class="dropdown-menu">
            <?php if ($is_admin): ?>
            <li><a class="dropdown-item" href="admin_dashboard.php">
              <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
            </a></li>
            <li><a class="dropdown-item" href="admin.php">
              <i class="bi bi-upload me-2"></i>Upload Dataset
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a></li>
          </ul>
        </div>
      </div>

    </div>
  </header>

  <main class="main">

    <!-- User Dashboard Section -->
    <section id="dashboard" class="section" style="padding-top: 100px;">
      <div class="container">
        
        <!-- Welcome Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="dashboard-card welcome-gradient">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h2 class="mb-3">
                    <i class="bi bi-person-heart me-2"></i>
                    Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                  </h2>
                  <p class="mb-0 opacity-90">Discover new datasets, track your research progress, and collaborate with the academic community.</p>
                </div>
                <div class="col-md-4 text-end">
                  <i class="bi bi-graph-up" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- User Statistics -->
        <div class="row">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
              <i class="bi bi-chat-dots stat-icon"></i>
              <div class="stat-number"><?php echo $stats['my_reviews']; ?></div>
              <div class="stat-label">My Reviews</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-card">
              <i class="bi bi-download stat-icon"></i>
              <div class="stat-number"><?php echo $stats['my_downloads']; ?></div>
              <div class="stat-label">Downloads</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-card">
              <i class="bi bi-heart stat-icon"></i>
              <div class="stat-number"><?php echo $stats['favorite_category']; ?></div>
              <div class="stat-label">Favorite Category</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="stat-card">
              <i class="bi bi-star stat-icon"></i>
              <div class="stat-number"><?php echo $stats['avg_rating']; ?></div>
              <div class="stat-label">Avg Rating Given</div>
            </div>
          </div>
        </div>

        <!-- Recommended Datasets -->
        <div class="row mt-4">
          <div class="col-12" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-4">
                <i class="bi bi-stars me-2"></i>
                Recommended for You
              </h4>
              <div class="row">
                <?php foreach (array_slice($recommended_datasets, 0, 3) as $dataset): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                  <div class="dataset-card">
                    <div class="dataset-header">
                      <h6 class="mb-1"><?php echo htmlspecialchars($dataset['title']); ?></h6>
                      <small class="opacity-90"><?php echo htmlspecialchars($dataset['category']); ?></small>
                    </div>
                    <div class="p-3">
                      <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($dataset['description'], 0, 100)) . '...'; ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="category-badge"><?php echo $dataset['download_count']; ?> downloads</span>
                        <div>
                          <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-eye"></i>
                          </a>
                          <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-download"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- My Reviews and Recent Activity -->
        <div class="row mt-4">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-chat-square-text me-2"></i>
                My Recent Reviews
              </h4>
              <?php if (empty($my_reviews)): ?>
              <div class="text-center py-4">
                <i class="bi bi-chat-dots" style="font-size: 3rem; color: #e5e7eb;"></i>
                <p class="text-muted mt-2">You haven't written any reviews yet.</p>
                <a href="browse.php" class="btn btn-primary">Browse Datasets</a>
              </div>
              <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($my_reviews as $review): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-1"><?php echo htmlspecialchars($review['dataset_title']); ?></h6>
                    <div class="rating-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <p class="mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                  <small class="text-muted">
                    <?php echo htmlspecialchars($review['category']); ?> • 
                    <?php echo date('M j, Y', strtotime($review['timestamp'])); ?>
                  </small>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-activity me-2"></i>
                Recent Platform Activity
              </h4>
              <?php foreach ($recent_activity as $activity): ?>
              <div class="activity-item">
                <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                <p class="text-muted small mb-1"><?php echo htmlspecialchars($activity['category']); ?> dataset added</p>
                <small class="text-muted"><?php echo date('M j, Y', strtotime($activity['upload_date'])); ?></small>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Popular Categories -->
        <div class="row mt-4">
          <div class="col-12" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-4">
                <i class="bi bi-collection me-2"></i>
                Explore Popular Categories
              </h4>
              <div class="row">
                <?php foreach ($popular_categories as $category): ?>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                  <a href="browse.php?category=<?php echo urlencode($category['category']); ?>" class="text-decoration-none">
                    <div class="text-center p-3 border rounded hover-shadow">
                      <i class="bi bi-folder" style="font-size: 2rem; color: #2563eb;"></i>
                      <h6 class="mt-2 mb-1"><?php echo htmlspecialchars($category['category']); ?></h6>
                      <small class="text-muted"><?php echo $category['count']; ?> datasets</small>
                    </div>
                  </a>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
          <div class="col-12" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-lightning me-2"></i>
                Quick Actions
              </h4>
              <div class="row">
                <div class="col-md-3 mb-3">
                  <a href="browse.php" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>
                    Browse Datasets
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="browse.php?sort=newest" class="btn btn-outline-primary w-100">
                    <i class="bi bi-clock me-2"></i>
                    Latest Uploads
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="browse.php?sort=popular" class="btn btn-outline-success w-100">
                    <i class="bi bi-trophy me-2"></i>
                    Most Popular
                  </a>
                </div>
                <?php if ($is_admin): ?>
                <div class="col-md-3 mb-3">
                  <a href="admin_dashboard.php" class="btn btn-outline-warning w-100">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Admin Panel
                  </a>
                </div>
                <?php else: ?>
                <div class="col-md-3 mb-3">
                  <a href="browse.php?sort=rating" class="btn btn-outline-info w-100">
                    <i class="bi bi-star me-2"></i>
                    Top Rated
                  </a>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

  </main>

  <footer id="footer" class="footer light-background">
    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">DataShare Platform</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        User Dashboard - Dataset Sharing and Collaboration Platform
      </div>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
