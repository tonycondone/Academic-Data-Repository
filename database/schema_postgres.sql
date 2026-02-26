-- PostgreSQL schema for Academic Dataset Platform
-- Optimized for Supabase

-- 1. Users table
CREATE TABLE IF NOT EXISTS users (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'user', 'faculty', 'student')),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login  TIMESTAMP NULL,
    is_active   BOOLEAN DEFAULT TRUE
);

CREATE INDEX IF NOT EXISTS idx_users_email       ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_role        ON users (role);

-- 2. Datasets table
CREATE TABLE IF NOT EXISTS datasets (
    id            SERIAL PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    filename      VARCHAR(255) NOT NULL,
    category      VARCHAR(100) NOT NULL,
    description   TEXT,
    file_path     VARCHAR(500) NOT NULL,
    file_size     BIGINT NOT NULL,
    uploaded_by   INT NOT NULL,
    upload_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 0,
    view_count    INT DEFAULT 0,
    is_active     BOOLEAN DEFAULT TRUE,
    is_featured   BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_datasets_uploaded_by FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_datasets_category        ON datasets (category);
CREATE INDEX IF NOT EXISTS idx_datasets_uploader        ON datasets (uploaded_by);
CREATE INDEX IF NOT EXISTS idx_datasets_upload_date     ON datasets (upload_date);

-- 3. Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id          SERIAL PRIMARY KEY,
    user_id     INT NOT NULL,
    dataset_id  INT NOT NULL,
    rating      INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_reviews_dataset FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    CONSTRAINT unique_user_dataset UNIQUE (user_id, dataset_id)
);

CREATE INDEX IF NOT EXISTS idx_reviews_dataset ON reviews (dataset_id);
CREATE INDEX IF NOT EXISTS idx_reviews_user    ON reviews (user_id);

-- 4. Download tracking table
CREATE TABLE IF NOT EXISTS downloads (
    id            SERIAL PRIMARY KEY,
    dataset_id    INT NOT NULL,
    user_id       INT NULL,
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_downloads_dataset FOREIGN KEY (dataset_id)
        REFERENCES datasets(id) ON DELETE CASCADE,
    CONSTRAINT fk_downloads_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_downloads_dataset ON downloads (dataset_id);
CREATE INDEX IF NOT EXISTS idx_downloads_user ON downloads (user_id);
CREATE INDEX IF NOT EXISTS idx_downloads_downloaded ON downloads (downloaded_at);
CREATE INDEX idx_downloads_dataset_date ON downloads(dataset_id, downloaded_at);

-- Projects table for collaborative data projects
CREATE TABLE projects (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    owner_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'completed', 'archived', 'suspended')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline DATE NULL,
    max_members INT DEFAULT 50,
    is_public BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_projects_owner ON projects (owner_id);
CREATE INDEX idx_projects_status ON projects (status);
CREATE INDEX idx_projects_created ON projects (created_at);

-- Project members with specific roles within projects
CREATE TABLE project_members (
    id SERIAL PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(20) DEFAULT 'collaborator' CHECK (role IN ('owner', 'collaborator', 'viewer')),
    permissions JSONB,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    invited_by INT,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('active', 'pending', 'removed')),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (project_id, user_id)
);

CREATE INDEX idx_project_members_project ON project_members (project_id);
CREATE INDEX idx_project_members_user ON project_members (user_id);
CREATE INDEX idx_project_members_status ON project_members (status);

-- Files table for storing file metadata
CREATE TABLE files (
    id SERIAL PRIMARY KEY,
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
    tags JSONB,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_files_project ON files (project_id);
CREATE INDEX idx_files_filename ON files (filename);
CREATE INDEX idx_files_type ON files (file_type);
CREATE INDEX idx_files_uploader ON files (uploaded_by);

-- File versions for version control
CREATE TABLE file_versions (
    id SERIAL PRIMARY KEY,
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
    UNIQUE (file_id, version_number)
);

CREATE INDEX idx_file_versions_file ON file_versions (file_id);
CREATE INDEX idx_file_versions_version ON file_versions (version_number);
CREATE INDEX idx_file_versions_branch ON file_versions (branch_id);
CREATE INDEX idx_file_versions_created ON file_versions (created_at);

-- Branches for version control
CREATE TABLE branches (
    id SERIAL PRIMARY KEY,
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
    UNIQUE (project_id, name)
);

CREATE INDEX idx_branches_project ON branches (project_id);
CREATE INDEX idx_branches_creator ON branches (created_by);
CREATE INDEX idx_branches_active ON branches (is_active);

-- Activity log for tracking all project activities
CREATE TABLE activity_log (
    id SERIAL PRIMARY KEY,
    project_id INT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(20) NOT NULL CHECK (target_type IN ('project', 'file', 'version', 'branch', 'member', 'user', 'dataset')),
    target_id INT,
    details JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_activity_log_project ON activity_log (project_id);
CREATE INDEX idx_activity_log_user ON activity_log (user_id);
CREATE INDEX idx_activity_log_action ON activity_log (action);
CREATE INDEX idx_activity_log_created ON activity_log (created_at);

-- Notifications table
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user ON notifications (user_id);
CREATE INDEX idx_notifications_read ON notifications (is_read);
CREATE INDEX idx_notifications_created ON notifications (created_at);

-- Login attempts for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    username VARCHAR(255) NULL
);

CREATE INDEX idx_login_attempts_ip_time ON login_attempts (ip_address, attempt_time);
CREATE INDEX idx_login_attempts_username ON login_attempts (username);

-- Create a view for dataset overview with ratings
CREATE OR REPLACE VIEW dataset_overview AS
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

-- Function and Triggers for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
