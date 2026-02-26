-- Dataset Sharing and Collaboration Platform Database Schema
-- Based on Final Year Project Documentation

CREATE DATABASE IF NOT EXISTS dataset_platform;
USE dataset_platform;

-- Users Table (Simplified as per documentation)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Datasets Table (As per ERD in documentation)
CREATE TABLE datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    uploaded_by INT NOT NULL,
    download_count INT DEFAULT 0,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_upload_date (upload_date),
    INDEX idx_title (title),
    FULLTEXT(title, description)
);

-- Reviews Table (As per ERD in documentation)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_dataset (user_id, dataset_id),
    INDEX idx_dataset_rating (dataset_id, rating),
    INDEX idx_timestamp (timestamp)
);

-- Categories for filtering (predefined)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-folder',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, description, icon) VALUES
('Machine Learning', 'Datasets for machine learning and AI projects', 'fas fa-robot'),
('Business', 'Business and economics datasets', 'fas fa-briefcase'),
('Health', 'Medical and health-related datasets', 'fas fa-heartbeat'),
('Education', 'Educational datasets and academic research', 'fas fa-graduation-cap'),
('Social Sciences', 'Social research and demographic data', 'fas fa-users'),
('Environment', 'Environmental and climate datasets', 'fas fa-leaf'),
('Technology', 'Technology and engineering datasets', 'fas fa-microchip'),
('Finance', 'Financial and market datasets', 'fas fa-chart-line');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Administrator', 'admin@dataset-platform.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create view for dataset overview with ratings
CREATE VIEW dataset_overview AS
SELECT 
    d.id,
    d.title,
    d.filename,
    d.category,
    d.description,
    d.upload_date,
    d.file_size,
    d.download_count,
    u.name as uploader_name,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as review_count
FROM datasets d
JOIN users u ON d.uploaded_by = u.id
LEFT JOIN reviews r ON d.id = r.dataset_id
GROUP BY d.id;