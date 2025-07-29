<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$currentUser = $isLoggedIn ? $_SESSION : null;

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Dataset Sharing Platform</title>
  <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'A web-based platform for students, educators, and researchers to upload, explore, preview, and download datasets.'; ?>">
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
  
  <?php if (isset($extra_css)): ?>
  <!-- Page Specific CSS -->
  <?php echo $extra_css; ?>
  <?php endif; ?>
</head>

<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.php" class="logo d-flex align-items-center me-auto">
        <i class="bi bi-database me-2" style="font-size: 2rem; color: #2563eb;"></i>
        <h1 class="sitename">DataShare Platform</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
          <li><a href="browse.php" class="<?php echo $current_page == 'browse.php' ? 'active' : ''; ?>">Browse</a></li>
          <li><a href="datasets.php" class="<?php echo $current_page == 'datasets.php' ? 'active' : ''; ?>">Datasets</a></li>
          <?php if ($isLoggedIn): ?>
            <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <?php if ($isAdmin): ?>
              <li><a href="admin.php" class="<?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">Admin</a></li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <?php if ($isLoggedIn): ?>
        <div class="dropdown">
          <a class="btn-getstarted dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <?php if ($isAdmin): ?>
              <li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear me-2"></i>Admin Panel</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="btn-getstarted" href="login.php">Login</a>
      <?php endif; ?>

    </div>
  </header>

  <main class="main">
