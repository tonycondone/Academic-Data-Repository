-- Dataset Sharing and Collaboration Platform Database Schema
-- MySQL Database Setup

CREATE DATABASE IF NOT EXISTS dataset_platform;
USE dataset_platform;

-- Users table with role-based access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Datasets table for storing dataset information
CREATE TABLE datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(250) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_uploader (uploaded_by),
    INDEX idx_upload_date (upload_date)
);

-- Reviews table for dataset ratings and comments
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_dataset (user_id, dataset_id),
    INDEX idx_dataset (dataset_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating)
);

-- Sessions table for secure session management
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_active (is_active)
);

-- CSRF tokens table for form security
CREATE TABLE csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Download tracking table
CREATE TABLE downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dataset (dataset_id),
    INDEX idx_user (user_id),
    INDEX idx_downloaded (downloaded_at)
);

-- Create a view for dataset overview with ratings
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
    u.name as uploader_name,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as review_count
FROM datasets d
LEFT JOIN users u ON d.uploaded_by = u.id
LEFT JOIN reviews r ON d.id = r.dataset_id
WHERE d.is_active = TRUE
GROUP BY d.id, d.title, d.filename, d.category, d.description, d.file_path, d.file_size, d.uploaded_by, d.upload_date, d.download_count, u.name;

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) 
VALUES ('Administrator', 'admin@dataset-platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample datasets for testing
INSERT INTO datasets (title, filename, category, description, file_path, file_size, uploaded_by) VALUES
('Student Performance Dataset', 'student_performance.csv', 'Education', 'Dataset containing student performance metrics including grades, attendance, and demographic information.', 'uploads/student_performance.csv', 15420, 1),
('COVID-19 Global Cases', 'covid19_global_cases.csv', 'Health', 'Daily COVID-19 cases, deaths, and recoveries by country. Updated dataset containing global pandemic statistics.', 'uploads/covid19_global_cases.csv', 28750, 1),
('House Prices Dataset', 'house_prices.csv', 'Business', 'Real estate data including house prices, square footage, number of bedrooms, bathrooms, and location information.', 'uploads/house_prices.csv', 22100, 1),
('Iris Dataset', 'iris_dataset.csv', 'AI', 'Classic AI dataset with iris flower measurements and species classification.', 'uploads/iris_dataset.csv', 4800, 1),
('Stock Market Data', 'stock_market.csv', 'Finance', 'Historical stock market data including prices, volumes, and technical indicators for major companies.', 'uploads/stock_market.csv', 45600, 1),
('Climate Data', 'climate_data.csv', 'Environment', 'Global climate data including temperature, precipitation, and weather patterns over the past decade.', 'uploads/climate_data.csv', 67200, 1),
('Social Media Sentiment', 'social_sentiment.csv', 'Social Sciences', 'Social media posts with sentiment analysis labels for natural language processing research.', 'uploads/social_sentiment.csv', 33400, 1),
('IoT Sensor Data', 'iot_sensors.csv', 'ICT', 'Internet of Things sensor readings including temperature, humidity, and motion detection data.', 'uploads/iot_sensors.csv', 18900, 1);

-- Insert sample reviews
INSERT INTO reviews (user_id, dataset_id, rating, comment) VALUES
(1, 1, 5, 'Excellent dataset for educational research. Well-structured and comprehensive.'),
(1, 2, 4, 'Very useful for pandemic analysis. Data is accurate and up-to-date.'),
(1, 3, 5, 'Perfect for real estate analysis projects. Clean data with good coverage.'),
(1, 4, 5, 'Classic dataset, perfect for beginners in AI.'),
(1, 5, 4, 'Good financial data for analysis. Could use more recent entries.'),
(1, 6, 5, 'Comprehensive climate data. Excellent for environmental studies.'),
(1, 7, 4, 'Useful for sentiment analysis projects. Good variety of posts.'),
(1, 8, 4, 'Great IoT dataset for sensor data analysis and visualization.');

-- Update download counts
UPDATE datasets SET download_count = FLOOR(RAND() * 100) + 10;

-- Create indexes for better performance
CREATE INDEX idx_datasets_category_date ON datasets(category, upload_date);
CREATE INDEX idx_reviews_dataset_rating ON reviews(dataset_id, rating);
CREATE INDEX idx_downloads_dataset_date ON downloads(dataset_id, downloaded_at);
