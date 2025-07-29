<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle registration
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Database connection
        $host = 'localhost';
        $dbname = 'dataset_platform';
        $username = 'root';
        $db_password = '1212';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email address already registered.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
                $stmt->execute([$name, $email, $hashed_password]);
                
                $success = 'Registration successful! You can now login.';
                
                // Clear form data
                $_POST = array();
            }
        } catch(PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}

// Page specific variables
$page_title = 'Register';
$page_description = 'Create an account on Dataset Sharing and Collaboration Platform';
$body_class = 'register-page';

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
              <h5 class="card-title text-center pb-0 fs-4">Create an Account</h5>
              <p class="text-center small">Enter your personal details to create account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle me-1"></i>
              <?php echo htmlspecialchars($error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle me-1"></i>
              <?php echo htmlspecialchars($success); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form class="row g-3 needs-validation" method="POST" novalidate>
              <div class="col-12">
                <label for="name" class="form-label">Full Name</label>
                <input
                  type="text"
                  name="name"
                  class="form-control"
                  id="name"
                  required
                  value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                />
                <div class="invalid-feedback">Please enter your name!</div>
              </div>

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
                <div class="invalid-feedback">Please enter a valid email address!</div>
              </div>

              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required minlength="6" />
                <div class="invalid-feedback">Password must be at least 6 characters!</div>
              </div>

              <div class="col-12">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" id="confirm_password" required />
                <div class="invalid-feedback">Please confirm your password!</div>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="" id="acceptTerms" required>
                  <label class="form-check-label" for="acceptTerms">
                    I agree to the <a href="#">terms and conditions</a>
                  </label>
                  <div class="invalid-feedback">You must agree before submitting.</div>
                </div>
              </div>

              <div class="col-12">
                <button class="btn btn-primary w-100" type="submit">Create Account</button>
              </div>
            </form>

            <div class="mt-3">
              <p class="text-center mb-0">
                Already have an account? <a href="login.php">Login</a>
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
  
  // Password match validation
  const password = document.getElementById("password")
  const confirmPassword = document.getElementById("confirm_password")
  
  function validatePassword() {
    if (password.value != confirmPassword.value) {
      confirmPassword.setCustomValidity("Passwords do not match")
    } else {
      confirmPassword.setCustomValidity("")
    }
  }
  
  password.addEventListener("change", validatePassword)
  confirmPassword.addEventListener("keyup", validatePassword)
})()
</script>
';

// Include footer
include 'includes/footer.php';
?>
