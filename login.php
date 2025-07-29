<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle login
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Database connection
        $host = 'localhost';
        $dbname = 'dataset_platform';
        $username = 'root';
        $db_password = '1212';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch(PDOException $e) {
            $error = 'Database connection failed.';
        }
    }
}

// Page specific variables
$page_title = 'Login';
$page_description = 'Login to Dataset Sharing and Collaboration Platform';
$body_class = 'login-page';

// Include header
include 'includes/header.php';
?>

<section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

        <div class="card mb-3">
          <div class="card-body">
            <div class="pt-4 pb-2">
              <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
              <p class="text-center small">Enter your email & password to login</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle me-1"></i>
              <?php echo htmlspecialchars($error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form class="row g-3 needs-validation" method="POST" novalidate>
              <div class="col-12">
                <label for="email" class="form-label">Email Address</label>
                <input
                  type="email"
                  name="email"
                  class="form-control"
                  id="email"
                  required
                  value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                />
                <div class="invalid-feedback">Please enter your email address!</div>
              </div>

              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required />
                <div class="invalid-feedback">Please enter your password!</div>
              </div>

              <div class="col-12">
                <button class="btn btn-primary w-100" type="submit">Login</button>
              </div>
            </form>

            <div class="mt-3">
              <p class="text-center mb-0">
                Don't have an account? <a href="register.php">Create an account</a>
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<?php
// Page specific JS
$extra_js = '
<script>
// Form validation
(() => {
  "use strict"
  const forms = document.querySelectorAll(".needs-validation")
  Array.from(forms).forEach(form => {
    form.addEventListener("submit", event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add("was-validated")
    }, false)
  })
})()
</script>
';

// Include footer
include 'includes/footer.php';
?>
