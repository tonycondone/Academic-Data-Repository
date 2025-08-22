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
            <li><a href="index.php#features">Dataset Upload</a></li>
            <li><a href="index.php#features">Online Preview</a></li>
            <li><a href="index.php#features">Smart Search</a></li>
            <li><a href="index.php#features">Rating System</a></li>
            <li><a href="index.php#features">Secure Access</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Project Info</h4>
          <p>DataShare Platform</p>
          <p>Open for All</p>
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
      <p>© <span>Copyright</span> <strong class="px-1 sitename">DataShare Platform</strong> <span>2025</span> <span>All Rights Reserved</span></p>
      <div class="credits">
        DataShare Platform - Dataset Sharing and Collaboration Platform
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
  
  <?php if (isset($extra_js)): ?>
  <!-- Page Specific JS -->
  <?php echo $extra_js; ?>
  <?php endif; ?>

</body>

</html>
