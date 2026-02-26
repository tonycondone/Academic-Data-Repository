-- Migration to add login_attempts table for rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    username VARCHAR(255) NULL
);

CREATE INDEX idx_login_attempts_ip_time ON login_attempts (ip_address, attempt_time);
CREATE INDEX idx_login_attempts_username ON login_attempts (username);
