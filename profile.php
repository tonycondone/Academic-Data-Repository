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

$message = '';
$messageType = '';

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $message = 'Name and email are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $message = 'This email address is already in use.';
                $messageType = 'danger';
            } else {
                // Update basic info
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
                
                // Handle password change
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        $message = 'Current password is required to set a new password.';
                        $messageType = 'danger';
                    } elseif (!password_verify($currentPassword, $user['password'])) {
                        $message = 'Current password is incorrect.';
                        $messageType = 'danger';
                    } elseif (strlen($newPassword) < 6) {
                        $message = 'New password must be at least 6 characters long.';
                        $messageType = 'danger';
                    } elseif ($newPassword !== $confirmPassword) {
                        $message = 'New password and confirmation do not match.';
                        $messageType = 'danger';
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                        $message = 'Profile and password updated successfully!';
                        $messageType = 'success';
                    }
                } else {
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                }
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['name'] = $user['name'];
            }
        } catch(PDOException $e) {
            $message = 'Error updating profile. Please try again.';
            $messageType = 'danger';
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) as download_count FROM downloads WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$downloadStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent reviews
$stmt = $pdo->prepare("
    SELECT r.*, d.title as dataset_title 
    FROM reviews r 
    JOIN datasets d ON r.dataset_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page specific variables
$page_title = 'My Profile';
$page_description = 'Manage your account settings and view your activity';
$body_class = 'profile-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>My Profile</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Profile Section -->
<section class="profile section">
  <div class="container">
    <div class="row">
      <!-- Profile Form -->
      <div class="col-lg-8">
        <div class="profile-form">
          <h4>Account Information</h4>
          
          <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
              <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
              <?php echo htmlspecialchars($message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="role" class="form-label">Account Type</label>
                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
              </div>
              <div class="col-md-6 mb-3">
                <label for="member_since" class="form-label">Member Since</label>
                <input type="text" class="form-control" 
                       value="<?php echo date('M j, Y', strtotime($user['created_at'])); ?>" readonly>
              </div>
            </div>

            <hr class="my-4">

            <h5>Change Password</h5>
            <p class="text-muted small">Leave password fields empty if you don't want to change your password.</p>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
              </div>
              <div class="col-md-4 mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
              </div>
              <div class="col-md-4 mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Update Profile
              </button>
              <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Profile Sidebar -->
      <div class="col-lg-4">
        <!-- User Stats -->
        <div class="user-stats">
          <h5>Your Statistics</h5>
          <div class="stat-item">
            <div class="stat-number"><?php echo $userStats['review_count']; ?></div>
            <div class="stat-label">Reviews Written</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $downloadStats['download_count']; ?></div>
            <div class="stat-label">Datasets Downloaded</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo date('j', strtotime($user['created_at'])); ?></div>
            <div class="stat-label">Days as Member</div>
          </div>
        </div>

        <!-- Account Actions -->
        <div class="account-actions">
          <h5>Quick Actions</h5>
          <a href="browse.php" class="btn btn-outline-primary w-100 mb-2">
            <i class="bi bi-search me-2"></i>Browse Datasets
          </a>
          <a href="dashboard.php" class="btn btn-outline-secondary w-100 mb-2">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
          </a>
          <a href="logout.php" class="btn btn-outline-danger w-100">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Reviews -->
    <?php if (!empty($recentReviews)): ?>
    <div class="row mt-5">
      <div class="col-lg-12">
        <div class="recent-reviews">
          <h4>Your Recent Reviews</h4>
          
          <?php foreach ($recentReviews as $review): ?>
          <div class="review-item">
            <div class="review-header">
              <div class="dataset-info">
                <strong><?php echo htmlspecialchars($review['dataset_title']); ?></strong>
                <div class="review-rating">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?> text-warning"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="review-date">
                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
              </div>
            </div>
            <?php if ($review['comment']): ?>
            <div class="review-comment">
              <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
            </div>
            <?php endif; ?>
            <div class="review-actions">
              <a href="review.php?id=<?php echo $review['dataset_id']; ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit Review
              </a>
              <a href="preview.php?id=<?php echo $review['dataset_id']; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye me-1"></i>View Dataset
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
/* Profile page specific styles */
.page-title {
  background: #f8f9fa;
  padding: 40px 0;
  margin-bottom: 40px;
}

.page-title h2 {
  margin-bottom: 10px;
  color: #333;
}

.breadcrumb {
  background: none;
  padding: 0;
  margin: 0;
}

.profile-form {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.profile-form h4 {
  color: #333;
  margin-bottom: 1.5rem;
}

.profile-form h5 {
  color: #333;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.user-stats {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.user-stats h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.stat-item {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #eee;
}

.stat-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #2563eb;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
}

.account-actions {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.account-actions h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.recent-reviews {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.recent-reviews h4 {
  color: #333;
  margin-bottom: 2rem;
}

.review-item {
  padding: 1.5rem 0;
  border-bottom: 1px solid #eee;
}

.review-item:last-child {
  border-bottom: none;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.dataset-info strong {
  color: #333;
  display: block;
  margin-bottom: 0.25rem;
}

.review-rating {
  font-size: 0.9rem;
}

.review-date {
  color: #666;
  font-size: 0.85rem;
  text-align: right;
}

.review-comment {
  color: #555;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.review-actions {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
  }
  
  .review-header {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .review-date {
    text-align: left;
  }
  
  .review-actions {
    flex-direction: column;
  }
}
</style>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
