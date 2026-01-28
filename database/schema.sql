-- Dataset Sharing and Collaboration Platform Database Schema
-- MySQL Database Setup

CREATE DATABASE IF NOT EXISTS academic_collaboration;
USE academic_collaboration;

-- Users table with role-based access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty', 'student') NOT NULL DEFAULT 'student',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(70),
    student_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Projects table for collaborative data projects
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    owner_id INT NOT NULL,
    status ENUM('active', 'completed', 'archived', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deadline DATE NULL,
    max_members INT DEFAULT 50,
    is_public BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Project members with specific roles within projects
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'collaborator', 'viewer') DEFAULT 'collaborator',
    permissions JSON,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    invited_by INT,
    status ENUM('active', 'pending', 'removed') DEFAULT 'pending',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_project_user (project_id, user_id),
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- Files table for storing file metadata
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    current_version INT DEFAULT 1,
    description TEXT,
    tags JSON,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_filename (filename),
    INDEX idx_type (file_type),
    INDEX idx_uploader (uploaded_by)
);

-- File versions for version control
CREATE TABLE file_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    version_number INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    changes_description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    branch_id INT NULL,
    parent_version_id INT NULL,
    checksum VARCHAR(64),
    is_merged BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_version_id) REFERENCES file_versions(id) ON DELETE SET NULL,
    UNIQUE KEY unique_file_version (file_id, version_number),
    INDEX idx_file (file_id),
    INDEX idx_version (version_number),
    INDEX idx_branch (branch_id),
    INDEX idx_created (created_at)
);

-- Branches for version control
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_from_version INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_merged BOOLEAN DEFAULT FALSE,
    merged_at TIMESTAMP NULL,
    merged_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_from_version) REFERENCES file_versions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (merged_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_project_branch (project_id, name),
    INDEX idx_project (project_id),
    INDEX idx_creator (created_by),
    INDEX idx_active (is_active)
);

-- Activity log for tracking all project activities
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('project', 'file', 'version', 'branch', 'member') NOT NULL,
    target_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- File comments for collaboration
CREATE TABLE file_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    version_id INT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    line_number INT NULL,
    parent_comment_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_resolved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (version_id) REFERENCES file_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES file_comments(id) ON DELETE CASCADE,
    INDEX idx_file (file_id),
    INDEX idx_version (version_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
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

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, department) 
VALUES ('admin', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator', 'IT Department');

-- Create indexes for better performance
CREATE INDEX idx_files_project_type ON files(project_id, file_type);
CREATE INDEX idx_versions_file_created ON file_versions(file_id, created_at);
CREATE INDEX idx_activity_project_created ON activity_log(project_id, created_at);
CREATE INDEX idx_members_project_status ON project_members(project_id, status);