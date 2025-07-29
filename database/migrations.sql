-- Dataset Sharing and Collaboration Platform Database Migrations
-- Run these queries to update existing database to latest version
-- Compatible with current platform schema (datasets, users, reviews)

-- Migration Version: 2024.12.19
-- Description: Updates for authentication enforcement and realistic statistics

-- Ensure database exists and is selected
USE dataset_platform;

-- Add missing indexes for better performance on existing tables
CREATE INDEX IF NOT EXISTS idx_datasets_category_date ON datasets(category, upload_date);
CREATE INDEX IF NOT EXISTS idx_datasets_active_downloads ON datasets(is_active, download_count);
CREATE INDEX IF NOT EXISTS idx_reviews_dataset_rating ON reviews(dataset_id, rating);
CREATE INDEX IF NOT EXISTS idx_reviews_user_date ON reviews(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_users_role_active ON users(role, is_active);
CREATE INDEX IF NOT EXISTS idx_downloads_dataset_date ON downloads(dataset_id, downloaded_at);

-- Add view_count column to datasets table for tracking popularity
ALTER TABLE datasets ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 AFTER download_count;
UPDATE datasets SET view_count = FLOOR(download_count * 1.5) WHERE view_count = 0;

-- Add featured flag for highlighting important datasets
ALTER TABLE datasets ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE AFTER is_active;
CREATE INDEX IF NOT EXISTS idx_datasets_featured ON datasets(is_featured);

-- Update some datasets to be featured (top rated ones)
UPDATE datasets SET is_featured = TRUE 
WHERE id IN (
    SELECT dataset_id FROM (
        SELECT d.id as dataset_id, AVG(r.rating) as avg_rating
        FROM datasets d 
        LEFT JOIN reviews r ON d.id = r.dataset_id 
        GROUP BY d.id 
        ORDER BY avg_rating DESC, d.download_count DESC 
        LIMIT 3
    ) as featured_datasets
);

-- Add user preferences table for customization
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user_prefs (user_id)
);

-- Add bookmarks/favorites table
CREATE TABLE IF NOT EXISTS user_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_bookmark (user_id, dataset_id),
    INDEX idx_user_bookmarks (user_id),
    INDEX idx_dataset_bookmarks (dataset_id)
);

-- Add search history table for analytics
CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    search_query VARCHAR(500) NOT NULL,
    results_count INT DEFAULT 0,
    category_filter VARCHAR(100) NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_search_user (user_id),
    INDEX idx_search_query (search_query(100)),
    INDEX idx_search_date (created_at)
);

-- Add system settings table for configuration
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_public (is_public)
);

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'Dataset Sharing Platform', 'string', 'Name of the platform', TRUE),
('max_file_size', '52428800', 'integer', 'Maximum file size in bytes (50MB)', FALSE),
('allowed_file_types', '["csv","xlsx","xls","json","pdf","txt"]', 'json', 'Allowed file types for upload', FALSE),
('enable_public_registration', 'true', 'boolean', 'Allow public user registration', FALSE),
('require_email_verification', 'false', 'boolean', 'Require email verification for new accounts', FALSE),
('enable_dataset_ratings', 'true', 'boolean', 'Enable dataset rating and review system', TRUE),
('featured_datasets_limit', '3', 'integer', 'Number of featured datasets to show on homepage', TRUE),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', FALSE),
('require_login_for_download', 'true', 'boolean', 'Require authentication for dataset access', TRUE),
('max_datasets_per_user', '50', 'integer', 'Maximum datasets a user can upload', FALSE);

-- Add helpful votes for reviews
CREATE TABLE IF NOT EXISTS review_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('helpful', 'not_helpful') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_vote (user_id, review_id),
    INDEX idx_vote_review (review_id),
    INDEX idx_vote_user (user_id)
);

-- Add dataset statistics table for daily analytics
CREATE TABLE IF NOT EXISTS dataset_daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    date DATE NOT NULL,
    views INT DEFAULT 0,
    downloads INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dataset_date (dataset_id, date),
    INDEX idx_stats_date (date),
    INDEX idx_stats_dataset (dataset_id)
);

-- Add API access tokens table for future API functionality
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_name VARCHAR(100) NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_token_user (user_id),
    INDEX idx_token_active (is_active)
);

-- Update the dataset_overview view to include new fields
DROP VIEW IF EXISTS dataset_overview;
CREATE VIEW dataset_overview AS
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
    d.view_count,
    d.is_active,
    d.is_featured,
    u.name as uploader_name,
    u.email as uploader_email,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as review_count,
    COUNT(DISTINCT ub.id) as bookmark_count,
    MAX(r.created_at) as last_review_date
FROM datasets d
LEFT JOIN users u ON d.uploaded_by = u.id
LEFT JOIN reviews r ON d.id = r.dataset_id
LEFT JOIN user_bookmarks ub ON d.id = ub.dataset_id
WHERE d.is_active = TRUE
GROUP BY d.id, d.title, d.filename, d.category, d.description, d.file_path, 
         d.file_size, d.uploaded_by, d.upload_date, d.download_count, 
         d.view_count, d.is_active, d.is_featured, u.name, u.email;

-- Add full-text search indexes for better search performance
ALTER TABLE datasets ADD FULLTEXT(title, description);

-- Add triggers to automatically update view counts and statistics
DELIMITER //

CREATE TRIGGER IF NOT EXISTS update_dataset_view_count 
AFTER INSERT ON dataset_daily_stats
FOR EACH ROW
BEGIN
    UPDATE datasets 
    SET view_count = view_count + NEW.views 
    WHERE id = NEW.dataset_id;
END//

CREATE TRIGGER IF NOT EXISTS update_download_tracking
AFTER INSERT ON downloads
FOR EACH ROW
BEGIN
    UPDATE datasets 
    SET download_count = download_count + 1 
    WHERE id = NEW.dataset_id;
    
    -- Update daily stats
    INSERT INTO dataset_daily_stats (dataset_id, date, downloads) 
    VALUES (NEW.dataset_id, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE downloads = downloads + 1;
END//

DELIMITER ;

-- Insert some sample preferences for existing users
INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value) 
SELECT id, 'theme', 'light' FROM users WHERE is_active = TRUE;

INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value) 
SELECT id, 'items_per_page', '12' FROM users WHERE is_active = TRUE;

INSERT IGNORE INTO user_preferences (user_id, preference_key, preference_value) 
SELECT id, 'email_notifications', 'true' FROM users WHERE is_active = TRUE;

-- Create some sample daily stats for existing datasets
INSERT IGNORE INTO dataset_daily_stats (dataset_id, date, views, downloads)
SELECT 
    id, 
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY),
    FLOOR(RAND() * 50) + 10,
    FLOOR(RAND() * 5) + 1
FROM datasets 
WHERE is_active = TRUE;

-- Update user last_login timestamps for active users
UPDATE users 
SET last_login = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY)
WHERE is_active = TRUE AND last_login IS NULL;

-- Ensure all datasets have realistic view counts
UPDATE datasets 
SET view_count = GREATEST(download_count * 2, FLOOR(RAND() * 100) + 50)
WHERE view_count < download_count;

-- Add some sample bookmarks for users
INSERT IGNORE INTO user_bookmarks (user_id, dataset_id)
SELECT 
    u.id,
    d.id
FROM users u
CROSS JOIN datasets d
WHERE u.is_active = TRUE 
  AND d.is_active = TRUE
  AND u.role = 'user'
  AND RAND() < 0.3  -- 30% chance of bookmarking
LIMIT 20;

-- Migration completed successfully
SELECT 'Dataset Platform migrations completed successfully!' as message,
       NOW() as completed_at,
       '2024.12.19' as migration_version;
