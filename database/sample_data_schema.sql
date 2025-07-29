-- Dataset Sharing and Collaboration Platform Database Schema
-- WITH WORKING SAMPLE DATA
-- MySQL Database Setup

DROP DATABASE IF EXISTS dataset_platform;
CREATE DATABASE dataset_platform;
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
    title VARCHAR(255) NOT NULL,
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

-- Insert sample users
INSERT INTO users (name, email, password, role) VALUES
('System Administrator', 'admin@dataset-platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Sarah Johnson', 'sarah.johnson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Prof. Michael Chen', 'michael.chen@research.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Dr. Emily Rodriguez', 'emily.rodriguez@datalab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Research Assistant', 'assistant@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Insert sample datasets matching the CSV files we created
INSERT INTO datasets (title, filename, category, description, file_path, file_size, uploaded_by) VALUES
('Student Performance Analysis Dataset', 'student_performance.csv', 'Education', 'Comprehensive dataset containing student performance metrics including grades, attendance, and demographic information for academic research and analysis.', 'uploads/student_performance.csv', 1542, 1),
('COVID-19 Global Cases Tracking', 'covid19_global_cases.csv', 'Health', 'Daily COVID-19 cases, deaths, and recoveries by country. Updated dataset containing global pandemic statistics for epidemiological research.', 'uploads/covid19_global_cases.csv', 2875, 1),
('Real Estate Market Analysis', 'house_prices.csv', 'Business', 'Real estate data including house prices, square footage, number of bedrooms, bathrooms, and location information for market analysis.', 'uploads/house_prices.csv', 2210, 1),
('Iris Flower Classification Dataset', 'iris_dataset.csv', 'Machine Learning', 'Classic machine learning dataset with iris flower measurements and species classification. Perfect for beginners in data science.', 'uploads/iris_dataset.csv', 4800, 1),
('Stock Market Financial Data', 'stock_market.csv', 'Finance', 'Historical stock market data including prices, volumes, and technical indicators for major technology companies and market analysis.', 'uploads/stock_market.csv', 4560, 1),
('Global Climate Monitoring Data', 'climate_data.csv', 'Environment', 'Global climate data including temperature, precipitation, humidity, and weather patterns from major cities worldwide.', 'uploads/climate_data.csv', 6720, 1),
('Social Media Sentiment Analysis', 'social_sentiment.csv', 'Social Sciences', 'Social media posts with sentiment analysis labels for natural language processing research and social behavior studies.', 'uploads/social_sentiment.csv', 3340, 1),
('IoT Sensor Network Data', 'iot_sensors.csv', 'Technology', 'Internet of Things sensor readings including temperature, humidity, motion detection, and environmental monitoring data.', 'uploads/iot_sensors.csv', 1890, 1);

-- Insert sample reviews
INSERT INTO reviews (user_id, dataset_id, rating, comment) VALUES
(2, 1, 5, 'Excellent dataset for educational research. Well-structured with comprehensive student metrics. Very useful for academic analysis.'),
(3, 1, 4, 'Good quality data with clear column headers. Would benefit from more demographic diversity in the sample.'),
(2, 2, 5, 'Very useful for pandemic analysis. Data is accurate and up-to-date. Essential for epidemiological research.'),
(4, 2, 4, 'Comprehensive COVID-19 data. Good for time series analysis. Could use more granular geographic data.'),
(3, 3, 5, 'Perfect for real estate analysis projects. Clean data with good coverage of different property types and locations.'),
(5, 3, 4, 'Solid dataset for housing market research. Price data seems realistic and well-distributed across regions.'),
(2, 4, 5, 'Classic dataset, perfect for beginners in machine learning. Clean, well-labeled, and ideal for classification tasks.'),
(4, 4, 5, 'Iris dataset is a must-have for ML education. Simple yet effective for demonstrating classification algorithms.'),
(3, 5, 4, 'Good financial data for analysis. Stock prices and volumes are realistic. Could use more recent entries for current market analysis.'),
(5, 5, 4, 'Useful for financial modeling and analysis. Good variety of tech stocks with proper market data structure.'),
(2, 6, 5, 'Comprehensive climate data. Excellent for environmental studies and weather pattern analysis. Very detailed measurements.'),
(4, 6, 4, 'Great dataset for climate research. Good geographic coverage and multiple weather parameters included.'),
(3, 7, 4, 'Useful for sentiment analysis projects. Good variety of social media posts with proper sentiment labels and confidence scores.'),
(5, 7, 4, 'Well-structured sentiment data. Good for NLP research and social media analysis. Covers multiple platforms effectively.'),
(2, 8, 4, 'Great IoT dataset for sensor data analysis and visualization. Good variety of sensor types and realistic readings.'),
(4, 8, 5, 'Excellent for IoT research. Comprehensive sensor data with proper timestamps and realistic environmental readings.');

-- Update download counts with realistic numbers
UPDATE datasets SET download_count = CASE id
    WHEN 1 THEN 156  -- Student Performance
    WHEN 2 THEN 243  -- COVID-19 Data
    WHEN 3 THEN 189  -- House Prices
    WHEN 4 THEN 312  -- Iris Dataset (popular for ML)
    WHEN 5 THEN 198  -- Stock Market
    WHEN 6 THEN 134  -- Climate Data
    WHEN 7 THEN 167  -- Social Sentiment
    WHEN 8 THEN 145  -- IoT Sensors
END;

-- Create indexes for better performance
CREATE INDEX idx_datasets_category_date ON datasets(category, upload_date);
CREATE INDEX idx_reviews_dataset_rating_unique ON reviews(dataset_id, rating);
CREATE INDEX idx_downloads_dataset_date ON downloads(dataset_id, downloaded_at);

-- Insert some download tracking records for realism (after datasets are created)
INSERT INTO downloads (dataset_id, user_id, ip_address, downloaded_at) VALUES
(1, 2, '192.168.1.100', '2024-01-01 10:30:00'),
(1, 3, '192.168.1.101', '2024-01-01 14:15:00'),
(2, 2, '192.168.1.100', '2024-01-01 16:45:00'),
(3, 1, '192.168.1.102', '2024-01-02 09:20:00'),
(4, 2, '192.168.1.103', '2024-01-02 11:30:00'),
(5, 3, '192.168.1.101', '2024-01-02 15:10:00'),
(6, 2, '192.168.1.100', '2024-01-02 17:25:00'),
(7, 1, '192.168.1.102', '2024-01-03 08:40:00'),
(8, 2, '192.168.1.103', '2024-01-03 12:15:00'),
(1, 3, '192.168.1.101', '2024-01-03 14:50:00');
