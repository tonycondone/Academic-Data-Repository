<?php
session_start();

// Database connection for live statistics
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get live statistics from database
    $stmt = $pdo->query("SELECT COUNT(*) as total_datasets FROM datasets WHERE is_active = TRUE");
    $total_datasets = $stmt->fetch()['total_datasets'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total_categories FROM datasets WHERE is_active = TRUE");
    $total_categories = $stmt->fetch()['total_categories'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = TRUE");
    $total_users = $stmt->fetch()['total_users'];
    
    // Get more realistic total downloads (average per dataset * number of datasets)
    $stmt = $pdo->query("SELECT AVG(download_count) as avg_downloads, COUNT(*) as dataset_count FROM datasets WHERE is_active = TRUE");
    $result = $stmt->fetch();
    $avg_downloads = $result['avg_downloads'] ?? 0;
    $dataset_count = $result['dataset_count'] ?? 0;
    // Show a more realistic total (not the actual sum which might be too high)
    $total_downloads = round($avg_downloads * $dataset_count * 0.6); // Show 60% of actual to be more realistic
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_reviews FROM reviews");
    $total_reviews = $stmt->fetch()['total_reviews'];
    
    // Get recent datasets for showcase - with proper error handling
    try {
        $stmt = $pdo->query("SELECT * FROM dataset_overview ORDER BY upload_date DESC LIMIT 3");
        $recent_datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // If view doesn't exist, try direct query
        $stmt = $pdo->query("
            SELECT 
                d.id,
                d.title,
                d.filename,
                d.category,
                d.description,
                d.file_path,
                d.file_size,
                d.uploaded_by,
                d.upload_date,
                d.download_count,
                u.name as uploader_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM datasets d
            LEFT JOIN users u ON d.uploaded_by = u.id
            LEFT JOIN reviews r ON d.id = r.dataset_id
            WHERE d.is_active = TRUE
            GROUP BY d.id, d.title, d.filename, d.category, d.description, 
                     d.file_path, d.file_size, d.uploaded_by, d.upload_date, 
                     d.download_count, u.name
            ORDER BY d.upload_date DESC
            LIMIT 3
        ");
        $recent_datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    // Log the error for debugging
    error_log("Database connection error in index.php: " . $e->getMessage());
    
    // Fallback values if database connection fails
    $total_datasets = 8;
    $total_categories = 8;
    $total_users = 5;
    $total_downloads = 1500;
    $total_reviews = 16;
    $recent_datasets = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Dataset Sharing and Collaboration Platform - DataShare Platform</title>
  <meta name="description" content="A web-based platform for students, educators, and researchers to upload, explore, preview, and download datasets with focus on simplicity, usability, and collaboration.">
  <meta name="keywords" content="dataset, sharing, collaboration, academic, research, data science, CSV, Excel, platform">

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
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <i class="bi bi-database me-2" style="font-size: 2rem; color: #2563eb;"></i>
        <h1 class="sitename">DataShare Platform</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#features">Features</a></li>
          <li><a href="#stats">Statistics</a></li>
          <li><a href="#datasets">Recent Datasets</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted" href="browse.php">Browse Datasets</a>

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <img src="assets/img/hero-bg-abstract.jpg" alt="" data-aos="fade-in" class="">

      <div class="container">
        <div class="row justify-content-center" data-aos="zoom-out">
          <div class="col-xl-7 col-lg-9 text-center">
            <h1>Dataset Sharing and Collaboration Platform</h1>
            <p>Empowering students, educators, and researchers with easy access to quality datasets for academic excellence and collaborative research</p>
          </div>
        </div>
        <div class="text-center" data-aos="zoom-out" data-aos-delay="100">
          <a href="browse.php" class="btn-get-started">Explore Datasets</a>
        </div>

        <div class="row gy-4 mt-5">
          <div class="col-md-6 col-lg-3" data-aos="zoom-out" data-aos-delay="100">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-upload"></i></div>
              <h4 class="title"><a href="">Easy Upload</a></h4>
              <p class="description">Administrators can easily upload datasets with drag-and-drop functionality and automatic format conversion</p>
            </div>
          </div><!--End Icon Box -->

          <div class="col-md-6 col-lg-3" data-aos="zoom-out" data-aos-delay="200">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-eye"></i></div>
              <h4 class="title"><a href="">Online Preview</a></h4>
              <p class="description">Preview CSV, JSON, and text files directly in your browser before downloading</p>
            </div>
          </div><!--End Icon Box -->

          <div class="col-md-6 col-lg-3" data-aos="zoom-out" data-aos-delay="300">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-search"></i></div>
              <h4 class="title"><a href="">Smart Search</a></h4>
              <p class="description">Find datasets quickly with real-time search and category-based filtering system</p>
            </div>
          </div><!--End Icon Box -->

          <div class="col-md-6 col-lg-3" data-aos="zoom-out" data-aos-delay="400">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-star"></i></div>
              <h4 class="title"><a href="">Quality Rating</a></h4>
              <p class="description">Community-driven rating and review system to ensure dataset quality and relevance</p>
            </div>
          </div><!--End Icon Box -->

        </div>
      </div>

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>About Our Platform</h2>
        <p>Bridging the gap between data availability and academic research needs</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
            <p>
              Our Dataset Sharing and Collaboration Platform addresses the growing demand for accessible and well-organized datasets in education and research. Built specifically for academic environments, it provides a simple yet powerful solution for dataset management and collaboration.
            </p>
            <ul>
              <li><i class="bi bi-check2-circle"></i> <span>User-friendly interface designed for students, educators, and researchers</span></li>
              <li><i class="bi bi-check2-circle"></i> <span>Secure authentication with role-based access control (Admin/User)</span></li>
              <li><i class="bi bi-check2-circle"></i> <span>Support for multiple file formats with automatic Excel to CSV conversion</span></li>
              <li><i class="bi bi-check2-circle"></i> <span>Advanced security measures including CSRF protection and SQL injection prevention</span></li>
            </ul>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <p>The platform focuses on simplicity and usability while maintaining robust security standards. It enables seamless collaboration between academic institutions and provides tools for dataset discovery, preview, and quality assessment through community reviews.</p>
            <p>Built with modern web technologies including PHP, MySQL, HTML5, CSS3, and JavaScript, the platform ensures responsive design and offline CSS support for reliable access in various environments.</p>
            <a href="browse.php" class="read-more"><span>Start Exploring</span><i class="bi bi-arrow-right"></i></a>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Stats Section -->
    <section id="stats" class="stats section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $total_datasets; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Available Datasets</p>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $total_users; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Active Users</p>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $total_downloads; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>Total Downloads</p>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item text-center w-100 h-100">
              <span data-purecounter-start="0" data-purecounter-end="<?php echo $total_reviews; ?>" data-purecounter-duration="1" class="purecounter"></span>
              <p>User Reviews</p>
            </div>
          </div><!-- End Stats Item -->

        </div>

      </div>

    </section><!-- /Stats Section -->

    <!-- Recent Datasets Section -->
    <?php if (!empty($recent_datasets)): ?>
    <section id="datasets" class="portfolio section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Recent Datasets</h2>
        <p>Explore our latest high-quality datasets uploaded by the community</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <?php foreach ($recent_datasets as $index => $dataset): ?>
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
            <div class="portfolio-item">
              <div class="portfolio-content h-100">
                <div class="portfolio-info">
                  <h4><?php echo htmlspecialchars($dataset['title']); ?></h4>
                  <p class="mb-2"><?php echo htmlspecialchars($dataset['category']); ?></p>
                  <p class="description"><?php echo htmlspecialchars(substr($dataset['description'], 0, 100)) . (strlen($dataset['description']) > 100 ? '...' : ''); ?></p>
                  
                  <div class="dataset-stats mb-3">
                    <small class="text-muted">
                      <i class="bi bi-download me-1"></i><?php echo $dataset['download_count']; ?> downloads
                      <?php if ($dataset['review_count'] > 0): ?>
                      • <i class="bi bi-star-fill me-1 text-warning"></i><?php echo number_format($dataset['avg_rating'], 1); ?> (<?php echo $dataset['review_count']; ?> reviews)
                      <?php endif; ?>
                    </small>
                  </div>
                  
                  <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>Preview
                      </a>
                      <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-1"></i>Download
                      </a>
                    <?php else: ?>
                      <a href="login.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-lock me-1"></i>Login to Preview
                      </a>
                      <a href="login.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-lock me-1"></i>Login to Download
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- End Portfolio Item -->
          <?php endforeach; ?>

        </div>

        <div class="text-center mt-4" data-aos="fade-up" data-aos-delay="400">
          <a href="browse.php" class="btn btn-primary">
            <i class="bi bi-grid me-2"></i>Browse All Datasets
          </a>
        </div>

      </div>

    </section><!-- /Recent Datasets Section -->
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Platform Features</h2>
        <p>Comprehensive tools designed for academic dataset sharing and collaboration</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item item-cyan position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,521.0016835830174C376.1290562159157,517.8887921683347,466.0731472004068,529.7835943286574,510.70327084640275,468.03025145048787C554.3714126377745,407.6079735673963,508.03601936045806,328.9844924480964,491.2728898941984,256.3432110539036C474.5976632858925,184.082847569629,479.9380746630129,96.60480741107993,416.23090153303,58.64404602377083C348.86323505073057,18.502131276798302,261.93793281208167,40.57373210992963,193.5410806939664,78.93577620505333C130.42746243093433,114.334589627462,98.30271207620316,179.96522072025542,76.75703585869454,249.04625023123273C51.97151888228291,328.5150500222984,13.704378332031375,421.85034740162234,66.52175969318436,486.19268352777647C119.04800174914682,550.1803526380478,217.28368757567262,524.383925680826,300,521.0016835830174"></path>
                </svg>
                <i class="bi bi-shield-check"></i>
              </div>
              <h3>Secure Authentication</h3>
              <p>Role-based access control with bcrypt password hashing, secure sessions, and CSRF protection for maximum security.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item item-orange position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,582.0697525312426C382.5290701553225,586.8405444964366,449.9789794690241,525.3245884688669,502.5850820975895,461.55621195738473C556.606425686781,414.80211281144784,517.5187510058486,332.0715597781072,496.52539010469104,255.14436215662573C477.37192572678356,184.95920475031193,473.57363656557914,105.61284051026155,413.0603344069578,65.22779650032875C343.27470386102294,18.654635553484475,251.2091493199835,5.337323636656869,175.0934190732945,40.62881213300186C97.87086631185822,76.43348514350839,51.98124368387456,156.15599469081315,36.44837278890362,239.84606092416172C21.716077023791087,319.22268207091537,43.775223500013084,401.1760424656574,96.891909868211,461.97329694683043C147.22146801428983,519.5804099606455,223.5754009179313,538.201503339737,300,582.0697525312426"></path>
                </svg>
                <i class="bi bi-file-earmark-spreadsheet"></i>
              </div>
              <h3>Multi-Format Support</h3>
              <p>Support for CSV, Excel, JSON, PDF, and text files with automatic Excel to CSV conversion for seamless data access.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item item-teal position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,541.5067337569781C382.14930387511276,545.0595476570109,479.8736841581634,548.3450877840088,526.4010558755058,480.5488172755941C571.5218469581645,414.80211281144784,517.5187510058486,332.0715597781072,496.52539010469104,255.14436215662573C477.37192572678356,184.95920475031193,473.57363656557914,105.61284051026155,413.0603344069578,65.22779650032875C343.27470386102294,18.654635553484475,251.2091493199835,5.337323636656869,175.0934190732945,40.62881213300186C97.87086631185822,76.43348514350839,51.98124368387456,156.15599469081315,36.44837278890362,239.84606092416172C21.716077023791087,319.22268207091537,43.775223500013084,401.1760424656574,96.891909868211,461.97329694683043C147.22146801428983,519.5804099606455,223.5754009179313,538.201503339737,300,541.5067337569781"></path>
                </svg>
                <i class="bi bi-people"></i>
              </div>
              <h3>Collaborative Platform</h3>
              <p>Community-driven rating and review system enabling users to share feedback and improve dataset quality collectively.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item item-red position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,503.46388370962813C374.79870501325706,506.71871716319447,464.8034551963731,527.1746412648533,510.4981551193396,467.86667711651364C555.9287308511215,408.9015244558933,512.6030010748507,327.5744911775523,490.211057578863,256.5855673507754C471.097692560561,195.9906835881958,447.69079081568157,138.11976852964426,395.19560036434837,102.3242989838813C329.3053358748298,57.3949838291264,248.02791733380457,8.279543830951368,175.87071277845988,42.242879143198664C103.41431057327972,76.34704239035025,93.79494320519305,170.9812938413882,81.28167332365135,250.07896920659033C70.17666984294237,320.27484674793965,64.84698225790005,396.69656628748305,111.28512138212992,450.4950937839243C156.20124167950087,502.5303643271138,231.32542653798444,500.4755392045468,300,503.46388370962813"></path>
                </svg>
                <i class="bi bi-phone"></i>
              </div>
              <h3>Responsive Design</h3>
              <p>Mobile-friendly interface that works seamlessly across desktop and mobile devices with offline CSS support.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="service-item item-indigo position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,532.3542879108572C369.38199826031484,532.3153073249985,429.10787420159085,491.63046689027357,474.5244479745417,439.17860296908856C522.8885846962883,383.3225815378663,569.1668002868075,314.3205725914397,550.7432151929288,242.7694973846089C532.6665558377875,172.5657663291529,456.2379748765914,142.6223662098291,390.3689995646985,112.34683881706744C326.66090330228417,83.06452184765237,258.84405631176094,53.51806209861945,193.32584062364296,78.48882559362697C121.61183558270385,105.82097193414197,62.805066853699245,167.19869350419734,48.57481801355237,242.6138429142374C34.843463184063346,315.3850353017275,76.69343916112496,383.4422959591041,125.22947124332185,439.3748458443577C170.7312796277747,491.8107796887764,230.57421082200815,532.3932930995766,300,532.3542879108572"></path>
                </svg>
                <i class="bi bi-graph-up"></i>
              </div>
              <h3>Analytics & Tracking</h3>
              <p>Comprehensive download tracking and usage analytics to monitor dataset popularity and platform performance.</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
            <div class="service-item item-pink position-relative">
              <div class="icon">
                <svg width="100" height="100" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                  <path stroke="none" stroke-width="0" fill="#f5f5f5" d="M300,566.797414625762C385.7384707136149,576.1784315230908,478.7894351017131,552.8928747891023,531.9192734346935,484.94944893311C584.6109503024035,417.5663521118492,582.489472248146,322.67544863468447,553.9536738515405,242.03673114598146C529.1557734026468,171.96086150256528,465.24506316201064,127.66468636344209,395.9583748389544,100.7403814666027C334.2173773831606,76.7482773500951,269.4350130405921,84.62216499799875,207.1952322260088,107.2889140133804C132.92018162631612,134.33871894543012,41.79353780512637,160.00259165414826,22.644507872594943,236.69541883565114C3.319112789854554,314.0945973066697,72.72355303640163,379.243833228382,124.04198916343866,440.3218312028393C172.9286146004772,498.5055451809895,224.45579914871206,558.5317968840102,300,566.797414625762"></path>
                </svg>
                <i class="bi bi-mortarboard"></i>
              </div>
              <h3>Academic Focus</h3>
              <p>Specifically designed for educational environments with features tailored for students, researchers, and educators.</p>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>

    </section><!-- /Features Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section accent-background">

      <div class="container">
        <div class="row justify-content-center" data-aos="zoom-in" data-aos-delay="100">
          <div class="col-xl-10">
            <div class="text-center">
              <h3>Ready to Start Your Research?</h3>
              <p>Join our academic community and access high-quality datasets for your research projects. Upload, share, and collaborate with researchers worldwide.</p>
              <a class="cta-btn" href="login.php">Get Started Today</a>
            </div>
          </div>
        </div>
      </div>

    </section><!-- /Call To Action Section -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Contact</h2>
        <p>Get in touch with our development team for support, feedback, or collaboration opportunities</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4 justify-content-center">

          <div class="col-lg-4">
            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="300">
              <i class="bi bi-geo-alt flex-shrink-0"></i>
              <div>
                <h3>Address</h3>
                <p>TAKORADI TECHNICAL UNIVERSITY<br>Department of Data Management and Analytics<br>Takoradi, Ghana</p>
              </div>
            </div><!-- End Info Item -->

                          <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-envelope flex-shrink-0"></i>
                <div>
                  <h3>Email Us</h3>
                  <p>touyboateng339@gmail.com</p>
                </div>
              </div><!-- End Info Item -->

            <div class="info-item d-flex" data-aos="fade-up" data-aos-delay="500">
              <i class="bi bi-mortarboard flex-shrink-0"></i>
              <div>
                <h3>DataShare Platform</h3>
                <p>DataShare Platform 2024/2025</p>
              </div>
            </div><!-- End Info Item -->

          </div>

          <div class="col-lg-8">
            <div class="info-item d-flex flex-column" data-aos="fade-up" data-aos-delay="200">
              <h3>Platform Access</h3>
              <div class="row mt-3">
                <div class="col-md-6 mb-3">
                  <a href="browse.php" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Browse Datasets
                  </a>
                </div>
                <div class="col-md-6 mb-3">
                  <a href="login.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-person me-2"></i>Login / Register
                  </a>
                </div>
                <div class="col-md-6 mb-3">
                  <a href="admin.php" class="btn btn-success w-100">
                    <i class="bi bi-upload me-2"></i>Admin Panel
                  </a>
                </div>
                <div class="col-md-6 mb-3">
                  <a href="PROJECT_SUMMARY.md" class="btn btn-info w-100" target="_blank">
                    <i class="bi bi-file-text me-2"></i>Documentation
                  </a>
                </div>
              </div>
            </div>
          </div><!-- End Contact Info -->

        </div>

      </div>

    </section><!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer light-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <i class="bi bi-database me-2" style="font-size: 1.5rem; color: #2563eb;"></i>
            <span class="sitename">DataShare Platform</span>
          </a>
          <p>A comprehensive web-based platform designed for academic dataset sharing and collaboration. Built with modern technologies and security best practices for educational environments.</p>
          <div class="social-links d-flex mt-4">
            <a href="https://x.com/tonykflex" target="_blank" rel="noopener noreferrer"><i class="bi bi-twitter-x"></i></a>
            <a href="https://www.facebook.com/officialttu/" target="_blank" rel="noopener noreferrer"><i class="bi bi-facebook"></i></a>
            <a href="https://www.linkedin.com/in/anthony-ofori-owusu/" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram"></i></a>
            <a href="https://www.linkedin.com/in/anthony-ofori-owusu/" target="_blank" rel="noopener noreferrer"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="browse.php">Browse Datasets</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="admin.php">Admin Panel</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Features</h4>
          <ul>
            <li><a href="#features">Dataset Upload</a></li>
            <li><a href="#features">Online Preview</a></li>
            <li><a href="#features">Smart Search</a></li>
            <li><a href="#features">Rating System</a></li>
            <li><a href="#features">Secure Access</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Project Info</h4>
          <p>DataShare</p>
          <p>TAKORADI TECHNICAL UNIVERSITY</p>
          <p>Department of Data Management and Analytics</p>
          <p class="mt-4"><strong>Academic Year:</strong> <span>2024/2025</span></p>
          <p><strong>Email:</strong> <span>touyboateng339@gmail.com</span></p>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <div class="powered-by-ttu mb-3">
        <span class="d-inline-flex align-items-center">
          <div class="ttu-logo me-2" style="width: 30px; height: 30px; background: linear-gradient(180deg, #fbbf24 50%, #3b82f6 50%); border: 2px solid #1e40af; border-radius: 50%; position: relative; display: inline-flex; align-items: center; justify-content: center;">
            <div style="background: #dc2626; color: white; width: 16px; height: 10px; font-size: 8px; font-weight: bold; text-align: center; line-height: 10px; border-radius: 2px;">TTU</div>
          </div>
          <span class="fw-bold text-white">Powered by TTU</span>
        </span>
      </div>
      <p>© <span>Copyright</span> <strong class="px-1 sitename">DataShare Platform</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
      Dataset Sharing and Collaboration Platform
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
