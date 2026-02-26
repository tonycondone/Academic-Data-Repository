<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRFToken();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? 'user'; // default to user
    
    // Check if locked out
    if (RateLimiter::isLockedOut($email)) {
        $error = 'Too many login attempts. Please try again in 15 minutes.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $supabaseAuth = new SupabaseAuth();
            $result = $supabaseAuth->signIn($email, $password);
            
            if ($result['status'] === 200) {
                $authData = $result['data'];
                $accessToken = $authData['access_token'];
                $userData = $authData['user'];
                
                // Get additional user info from our users table using SupabaseService
                $pdo = SupabaseService::getConnection();
                $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $localUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($localUser) {
                    if ($localUser['role'] !== $selected_role) {
                        $error = 'User role does not match selected login type.';
                        RateLimiter::recordAttempt($email, false);
                    } else {
                        // Success
                        RateLimiter::recordAttempt($email, true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $localUser['id'];
                        $_SESSION['name'] = $localUser['name'];
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $localUser['role'];
                        $_SESSION['supabase_token'] = $accessToken;
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Redirect based on role
                        if ($localUser['role'] === 'admin') {
                            header('Location: admin.php');
                        } else {
                            header('Location: dashboard.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'User account not found in local database.';
                    Logger::security("User account found in Supabase but not locally", ['email' => $email]);
                }
            } else {
                $error = 'Invalid email or password.';
                RateLimiter::recordAttempt($email, false);
                Logger::info("Failed login attempt", ['email' => $email]);
                
                $remaining = RateLimiter::getRemainingAttempts($email);
                if ($remaining > 0 && $remaining < 3) {
                    $error .= " You have $remaining attempts left.";
                }
            }
        } catch(Exception $e) {
            $error = 'Authentication failed: ' . $e->getMessage();
            Logger::error("Authentication exception", ['error' => $e->getMessage()]);
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
              <?php echo csrfTokenField(); ?>
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
                <label for="role" class="form-label">Login as</label>
                <select name="role" id="role" class="form-select" required>
                  <option value="user" <?php echo (($_POST['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User</option>
                  <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <div class="invalid-feedback">Please select a login role!</div>
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
