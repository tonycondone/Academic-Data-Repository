<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get dataset ID
$datasetId = (int)($_GET['id'] ?? 0);

if (!$datasetId) {
    header('Location: browse.php');
    exit;
}

$db = new Database();
$pdo = null;

try {
    $pdo = $db->getConnection();
} catch(PDOException $e) {
    // Database unavailable
}

$message = '';
$messageType = '';

// Get dataset details
$dataset = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM dataset_overview WHERE id = ?");
        $stmt->execute([$datasetId]);
        $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error
    }
}

if (!$dataset) {
    if (!$pdo) {
        $message = "Database connection unavailable.";
        $messageType = "danger";
        // Continue to show page but with error
    } else {
        header('Location: browse.php');
        exit;
    }
}

$existingReview = null;
if ($pdo && $dataset) {
    // Check if user has already reviewed this dataset
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND dataset_id = ?");
    $stmt->execute([$_SESSION['user_id'], $datasetId]);
    $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle review submission
if ($_POST && isset($_POST['submit_review'])) {
    if (!$pdo) {
        $message = "Service unavailable. Please try again later.";
        $messageType = "danger";
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $message = 'Please select a rating between 1 and 5 stars.';
            $messageType = 'danger';
        } else {
            try {
                if ($existingReview) {
                    // Update existing review
                    $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND dataset_id = ?");
                    $stmt->execute([$rating, $comment, $_SESSION['user_id'], $datasetId]);
                    $message = 'Your review has been updated successfully!';
                } else {
                    // Insert new review
                    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, dataset_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $datasetId, $rating, $comment]);
                    $message = 'Thank you for your review!';
                }
                $messageType = 'success';
                
                // Refresh existing review data
                $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND dataset_id = ?");
                $stmt->execute([$_SESSION['user_id'], $datasetId]);
                $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Refresh dataset information to get updated avg_rating and review_count
                $stmt = $pdo->prepare("SELECT * FROM dataset_overview WHERE id = ?");
                $stmt->execute([$datasetId]);
                $dataset = $stmt->fetch(PDO::FETCH_ASSOC);

                // AJAX response for live update
                if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                    // Get updated review count and average rating
                    $stmt = $pdo->prepare("SELECT COUNT(*) as review_count, AVG(rating) as avg_rating FROM reviews WHERE dataset_id = ?");
                    $stmt->execute([$datasetId]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'success' => true,
                        'message' => $message,
                        'review_count' => (int)$stats['review_count'],
                        'avg_rating' => round($stats['avg_rating'], 1)
                    ]);
                    exit;
                }
                
            } catch(PDOException $e) {
                $message = 'Database error occurred.';
                $messageType = 'danger';
            }
        }
    }
}

// Get all reviews for this dataset
$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.dataset_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$datasetId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page specific variables
$page_title = 'Review: ' . $dataset['title'];
$page_description = 'Rate and review dataset: ' . $dataset['title'];
$body_class = 'review-page';

// Include header
include 'includes/header.php';
?>

<!-- Page Title -->
<section class="page-title section">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <h2>Rate & Review Dataset</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="browse.php">Browse</a></li>
            <li class="breadcrumb-item"><a href="preview.php?id=<?php echo $dataset['id']; ?>">Preview</a></li>
            <li class="breadcrumb-item active" aria-current="page">Review</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Review Section -->
<section class="review section">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <!-- Dataset Info -->
        <div class="dataset-info">
          <h3><?php echo htmlspecialchars($dataset['title']); ?></h3>
          <div class="dataset-meta">
            <span class="meta-item">
              <i class="bi bi-person me-1"></i>
              by <?php echo htmlspecialchars($dataset['uploader_name']); ?>
            </span>
            <span class="meta-item">
              <i class="bi bi-tag me-1"></i>
              <?php echo htmlspecialchars($dataset['category']); ?>
            </span>
            <span class="meta-item">
              <i class="bi bi-calendar me-1"></i>
              <?php echo date('M j, Y', strtotime($dataset['upload_date'])); ?>
            </span>
          </div>
          <?php if ($dataset['description']): ?>
          <p class="dataset-description"><?php echo htmlspecialchars($dataset['description']); ?></p>
          <?php endif; ?>
        </div>

        <!-- Review Form -->
        <div class="review-form">
          <h4><?php echo $existingReview ? 'Update Your Review' : 'Write a Review'; ?></h4>
          
          <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
              <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
              <?php echo htmlspecialchars($message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-4">
              <label class="form-label">Rating *</label>
              <div class="star-rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" 
                       <?php echo ($existingReview && $existingReview['rating'] == $i) ? 'checked' : ''; ?> required>
                <label for="star<?php echo $i; ?>" class="star">
                  <i class="bi bi-star-fill"></i>
                </label>
                <?php endfor; ?>
              </div>
              <small class="form-text text-muted">Click on the stars to rate this dataset</small>
            </div>

            <div class="mb-4">
              <label for="comment" class="form-label">Your Review (Optional)</label>
              <textarea class="form-control" id="comment" name="comment" rows="4" 
                        placeholder="Share your thoughts about this dataset..."><?php echo $existingReview ? htmlspecialchars($existingReview['comment']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
              <button type="submit" name="submit_review" class="btn btn-primary">
                <i class="bi bi-star me-2"></i>
                <?php echo $existingReview ? 'Update Review' : 'Submit Review'; ?>
              </button>
              <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Preview
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <!-- Dataset Stats -->
        <div class="stats-sidebar">
          <h5>Dataset Statistics</h5>
          <div class="stat-item">
            <div class="stat-number"><?php echo number_format($dataset['avg_rating'], 1); ?></div>
            <div class="stat-label">Average Rating</div>
            <div class="rating-stars">
              <?php 
              $rating = round($dataset['avg_rating']);
              for ($i = 1; $i <= 5; $i++): 
              ?>
                <i class="bi bi-star<?php echo $i <= $rating ? '-fill' : ''; ?> text-warning"></i>
              <?php endfor; ?>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $dataset['review_count']; ?></div>
            <div class="stat-label">Total Reviews</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $dataset['download_count']; ?></div>
            <div class="stat-label">Downloads</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
          <h5>Quick Actions</h5>
          <a href="preview.php?id=<?php echo $dataset['id']; ?>" class="btn btn-outline-primary w-100 mb-2">
            <i class="bi bi-eye me-2"></i>Preview Dataset
          </a>
          <a href="download.php?id=<?php echo $dataset['id']; ?>" class="btn btn-primary w-100">
            <i class="bi bi-download me-2"></i>Download Dataset
          </a>
        </div>
      </div>
    </div>

    <!-- All Reviews -->
    <?php if (!empty($reviews)): ?>
    <div class="row mt-5">
      <div class="col-lg-12">
        <div class="all-reviews">
          <h4>All Reviews (<?php echo count($reviews); ?>)</h4>
          
          <?php foreach ($reviews as $review): ?>
          <div class="review-item">
            <div class="review-header">
              <div class="reviewer-info">
                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                <div class="review-rating">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?> text-warning"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="review-date">
                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                <?php if ($review['updated_at'] != $review['created_at']): ?>
                  <small class="text-muted">(edited)</small>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($review['comment']): ?>
            <div class="review-comment">
              <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
/* Review page specific styles */
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

.dataset-info {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.dataset-info h3 {
  color: #333;
  margin-bottom: 1rem;
}

.dataset-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
  color: #666;
}

.meta-item {
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.dataset-description {
  color: #666;
  line-height: 1.6;
}

.review-form {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.review-form h4 {
  color: #333;
  margin-bottom: 1.5rem;
}

.star-rating {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
  margin-bottom: 0.5rem;
}

.star-rating input {
  display: none;
}

.star-rating label {
  cursor: pointer;
  font-size: 1.5rem;
  color: #ddd;
  transition: color 0.2s;
  margin-right: 0.25rem;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
  color: #ffc107;
}

.form-actions {
  display: flex;
  gap: 1rem;
}

.stats-sidebar {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.stats-sidebar h5 {
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

.rating-stars {
  margin-top: 0.5rem;
}

.quick-actions {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.quick-actions h5 {
  color: #333;
  margin-bottom: 1.5rem;
}

.all-reviews {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.all-reviews h4 {
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

.reviewer-info strong {
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
}

@media (max-width: 768px) {
  .dataset-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
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
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
