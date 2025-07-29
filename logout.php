<?php
require_once 'includes/session.php';

// Initialize session
startSecureSession();

// Database connection for session cleanup
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clean up session from database
    if (isset($_SESSION['user_id'])) {
        $sessionId = session_id();
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE id = ? OR user_id = ?");
        $stmt->execute([$sessionId, $_SESSION['user_id']]);
    }
} catch(PDOException $e) {
    // Continue with logout even if DB cleanup fails
}

// Destroy session
logoutUser();

// Page specific variables
$page_title = 'Logged Out';
$page_description = 'You have been successfully logged out';
$body_class = 'logout-page';

// Include header
include 'includes/header.php';
?>

<!-- Logout Section -->
<section class="logout section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="logout-card text-center">
          <div class="logout-icon">
            <i class="bi bi-check-circle-fill text-success"></i>
          </div>
          <h3>Successfully Logged Out</h3>
          <p class="text-muted">You have been safely logged out of your account. Thank you for using our platform!</p>
          
          <div class="logout-actions">
            <a href="login.php" class="btn btn-primary">
              <i class="bi bi-box-arrow-in-right me-2"></i>Login Again
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
              <i class="bi bi-house me-2"></i>Go to Home
            </a>
          </div>
          
          <div class="security-note mt-4">
            <small class="text-muted">
              <i class="bi bi-shield-check me-1"></i>
              For your security, please close your browser if you're on a shared computer.
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Logout page specific styles */
.logout {
  padding: 100px 0;
  min-height: 60vh;
  display: flex;
  align-items: center;
}

.logout-card {
  background: white;
  border-radius: 15px;
  padding: 3rem 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.logout-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
}

.logout-card h3 {
  color: #333;
  margin-bottom: 1rem;
}

.logout-actions {
  margin: 2rem 0;
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.security-note {
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

@media (max-width: 576px) {
  .logout-actions {
    flex-direction: column;
  }
  
  .logout-card {
    padding: 2rem 1rem;
  }
}
</style>

<script>
// Auto-redirect after 10 seconds
setTimeout(function() {
    window.location.href = 'index.php';
}, 10000);

// Show countdown
let countdown = 10;
const countdownElement = document.createElement('div');
countdownElement.className = 'countdown mt-3';
countdownElement.innerHTML = '<small class="text-muted">Redirecting to home page in <span id="countdown">10</span> seconds...</small>';
document.querySelector('.logout-card').appendChild(countdownElement);

const countdownTimer = setInterval(function() {
    countdown--;
    document.getElementById('countdown').textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(countdownTimer);
    }
}, 1000);
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
