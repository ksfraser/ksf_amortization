-- OAuth2 Database Schema Migration
-- Date: 2026-04-03
-- Purpose: Support OAuth2 authentication and token management

-- OAuth2 Clients Table
-- Stores registered clients that can authenticate with the API
CREATE TABLE IF NOT EXISTS oauth2_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) NOT NULL UNIQUE,
    client_secret VARCHAR(255) NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    description TEXT,
    scopes VARCHAR(500),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuth2 Tokens Table
-- Tracks issued tokens for revocation and audit trail
CREATE TABLE IF NOT EXISTS oauth2_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    client_id VARCHAR(255) NOT NULL,
    token_type ENUM('access', 'refresh') NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_token_hash (token_hash),
    INDEX idx_revoked (revoked),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (client_id) REFERENCES oauth2_clients(client_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Authentication Logs Table
-- Tracks all authentication attempts for security audit
CREATE TABLE IF NOT EXISTS auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) NOT NULL,
    endpoint VARCHAR(500),
    ip_address VARCHAR(45),
    success TINYINT(1) NOT NULL,
    reason VARCHAR(500),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_success (success),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Scope Definitions Table (optional, for UI management)
-- Stores scope definitions for API documentation and client management
CREATE TABLE IF NOT EXISTS api_scopes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scope_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scope_name (scope_name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data: Built-in Scopes
INSERT INTO api_scopes (scope_name, description, category) VALUES
('read', 'Read-only access to loans and schedules', 'Core'),
('write', 'Create and modify loans and schedules', 'Core'),
('delete', 'Delete loans and related data', 'Core'),
('admin', 'Administrative access to all API operations', 'Admin'),
('analytics', 'Access to portfolio analytics and reports', 'Analytics'),
('reporting', 'Generate and export reports', 'Analytics'),
('webhooks', 'Manage webhook subscriptions', 'Integration'),
('audit', 'Access audit logs', 'Admin')
ON DUPLICATE KEY UPDATE description=VALUES(description);
