<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
session_start();

require_once __DIR__ . '/config/config.php';
$db = new Database();

try {
    $pdo = $db->getConnection();
} catch(PDOException $e) {
    $pdo = null;
}

// Get dashboard statistics
$stats = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM datasets");
    $stats['total_datasets'] = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT SUM(download_count) as total FROM datasets");
    $stats['total_downloads'] = $stmt->fetch()['total'] ?? 0;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $stats['total_reviews'] = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT * FROM datasets ORDER BY upload_date DESC LIMIT 5");
    $recent_datasets = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT * FROM datasets ORDER BY download_count DESC LIMIT 5");
    $popular_datasets = $stmt->fetchAll();
    $stmt = $pdo->query("
        SELECT r.*, d.title as dataset_title, u.name as user_name 
        FROM reviews r 
        JOIN datasets d ON r.dataset_id = d.id 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.timestamp DESC LIMIT 5
    ");
    $recent_reviews = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM datasets GROUP BY category ORDER BY count DESC");
    $category_stats = $stmt->fetchAll();
} else {
    $stats['total_datasets'] = 0;
    $stats['total_users'] = 0;
    $stats['total_downloads'] = 0;
    $stats['total_reviews'] = 0;
    $recent_datasets = [];
    $popular_datasets = [];
    $recent_reviews = [];
    $category_stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Admin Dashboard - DataShare Platform</title>
  <meta name="description" content="Admin dashboard for managing datasets, users, and platform analytics">

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
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #2563eb;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      color: #64748b;
      font-weight: 500;
    }
    
    .stat-icon {
      font-size: 3rem;
      color: #2563eb;
      margin-bottom: 1rem;
    }
    
    .table-responsive {
      border-radius: 8px;
      overflow: hidden;
    }
    
    .btn-action {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
      margin: 0 0.125rem;
    }
    
    .rating-stars {
      color: #fbbf24;
    }
    
    .category-bar {
      height: 20px;
      background: linear-gradient(90deg, #2563eb, #3b82f6);
      border-radius: 10px;
      margin-bottom: 0.5rem;
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <i class="bi bi-database me-2" style="font-size: 2rem; color: #2563eb;"></i>
        <h1 class="sitename">Admin Dashboard</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="browse.php">Browse Datasets</a></li>
          <li><a href="admin.php">Upload Dataset</a></li>
          <li><a href="#stats" class="active">Dashboard</a></li>
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
            <li><a class="dropdown-item" href="user_dashboard.php">
              <i class="bi bi-speedometer2 me-2"></i>User Dashboard
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a></li>
          </ul>
        </div>
      </div>

    </div>
  </header>

  <main class="main">

    <!-- Dashboard Stats Section -->
    <section id="stats" class="section" style="padding-top: 100px;">
      <div class="container">
        
        <!-- Welcome Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="dashboard-card">
              <h2 class="mb-3">
                <i class="bi bi-speedometer2 me-2"></i>
                Welcome to Admin Dashboard
              </h2>
              <p class="text-muted mb-0">Manage your dataset platform, monitor usage, and oversee user activities.</p>
            </div>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card stat-card">
              <i class="bi bi-database stat-icon"></i>
              <div class="stat-number"><?php echo $stats['total_datasets']; ?></div>
              <div class="stat-label">Total Datasets</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="dashboard-card stat-card">
              <i class="bi bi-people stat-icon"></i>
              <div class="stat-number"><?php echo $stats['total_users']; ?></div>
              <div class="stat-label">Registered Users</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="dashboard-card stat-card">
              <i class="bi bi-download stat-icon"></i>
              <div class="stat-number"><?php echo number_format($stats['total_downloads']); ?></div>
              <div class="stat-label">Total Downloads</div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="dashboard-card stat-card">
              <i class="bi bi-star stat-icon"></i>
              <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
              <div class="stat-label">User Reviews</div>
            </div>
          </div>
        </div>

        <!-- Recent Datasets -->
        <div class="row mt-4">
          <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-clock-history me-2"></i>
                Recent Datasets
              </h4>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Title</th>
                      <th>Category</th>
                      <th>Downloads</th>
                      <th>Upload Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recent_datasets as $dataset): ?>
                    <tr>
                      <td>
                        <strong><?php echo htmlspecialchars($dataset['title']); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($dataset['filename']); ?></small>
                      </td>
                      <td>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($dataset['category']); ?></span>
                      </td>
                      <td><?php echo $dataset['download_count']; ?></td>
                      <td><?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?></td>
                      <td>
                        <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary btn-action">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-success btn-action">
                          <i class="bi bi-download"></i>
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-pie-chart me-2"></i>
                Category Distribution
              </h4>
              <?php foreach ($category_stats as $category): ?>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span><?php echo htmlspecialchars($category['category']); ?></span>
                  <span><?php echo $category['count']; ?></span>
                </div>
                <div class="category-bar" style="width: <?php echo ($category['count'] / $stats['total_datasets']) * 100; ?>%"></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Popular Datasets and Recent Reviews -->
        <div class="row mt-4">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-trophy me-2"></i>
                Most Popular Datasets
              </h4>
              <div class="list-group list-group-flush">
                <?php foreach ($popular_datasets as $index => $dataset): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1"><?php echo htmlspecialchars($dataset['title']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($dataset['category']); ?></small>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-success rounded-pill"><?php echo $dataset['download_count']; ?> downloads</span>
                    <br>
                    <small class="text-muted">#<?php echo $index + 1; ?></small>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="dashboard-card">
              <h4 class="mb-3">
                <i class="bi bi-chat-dots me-2"></i>
                Recent Reviews
              </h4>
              <div class="list-group list-group-flush">
                <?php foreach ($recent_reviews as $review): ?>
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
                    by <?php echo htmlspecialchars($review['user_name']); ?> • 
                    <?php echo date('M j, Y', strtotime($review['timestamp'])); ?>
                  </small>
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
                  <a href="admin.php" class="btn btn-primary w-100">
                    <i class="bi bi-upload me-2"></i>
                    Upload Dataset
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="browse.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search me-2"></i>
                    Browse All Datasets
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="user_dashboard.php" class="btn btn-outline-success w-100">
                    <i class="bi bi-person me-2"></i>
                    User Dashboard
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="PROJECT_SUMMARY.md" class="btn btn-outline-info w-100" target="_blank">
                    <i class="bi bi-file-text me-2"></i>
                    Documentation
                  </a>
                </div>
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
        Admin Dashboard - Dataset Sharing and Collaboration Platform
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
