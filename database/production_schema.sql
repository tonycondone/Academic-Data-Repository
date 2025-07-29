-- Dataset Sharing and Collaboration Platform Database Schema
-- PRODUCTION VERSION - NO DEMO DATA
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

-- Create indexes for better performance
CREATE INDEX idx_datasets_category_date ON datasets(category, upload_date);
CREATE INDEX idx_reviews_dataset_rating ON reviews(dataset_id, rating);
CREATE INDEX idx_downloads_dataset_date ON downloads(dataset_id, downloaded_at);

-- PRODUCTION READY: No demo data inserted
-- Admin users should be created through the registration system or install.php
