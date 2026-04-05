-- Phase 18C: Additional OAuth2 Flows - Database Migration
-- Created: April 3, 2026
-- Purpose: Support Authorization Code Flow, PKCE, and OpenID Connect

-- ============================================================================
-- AUTHORIZATION CODES TABLE
-- ============================================================================
-- Stores temporary authorization codes (issued by authorization endpoint)
-- Codes expire after 10 minutes and can only be used once

CREATE TABLE IF NOT EXISTS oauth2_authorization_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE NOT NULL COMMENT 'Authorization code (single-use)',
    client_id VARCHAR(255) NOT NULL COMMENT 'OAuth2 client ID',
    user_id VARCHAR(255) COMMENT 'Resource owner user ID',
    redirect_uri TEXT NOT NULL COMMENT 'Must match exactlyfor security',
    scopes JSON COMMENT 'Requested scopes as JSON array',
    state VARCHAR(255) COMMENT 'State parameter for CSRF protection',
    code_challenge VARCHAR(255) COMMENT 'PKCE code challenge (SHA256 hash)',
    code_challenge_method VARCHAR(10) DEFAULT 'S256' COMMENT 'PKCE method: S256 (SHA256) or plain',
    expires_at TIMESTAMP NOT NULL COMMENT 'Expiration time (typically 10 min)',
    used_at TIMESTAMP NULL COMMENT 'When code was exchanged, NULL if unused',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_code (code),
    KEY idx_client_id (client_id),
    KEY idx_user_id (user_id),
    KEY idx_expires_at (expires_at),
    FOREIGN KEY (client_id) REFERENCES oauth2_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Temporary authorization codes for Authorization Code Flow';

-- ============================================================================
-- USER IDENTITIES TABLE
-- ============================================================================
-- Stores user information for OpenID Connect
-- Used by UserInfo endpoint and ID token generation

CREATE TABLE IF NOT EXISTS oauth2_user_identities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'Unique user identifier (subject)',
    email VARCHAR(255) UNIQUE COMMENT 'User email address',
    email_verified BOOLEAN DEFAULT FALSE COMMENT 'Email verification status',
    name VARCHAR(255) COMMENT 'Users full name',
    given_name VARCHAR(255) COMMENT 'Users first name',
    family_name VARCHAR(255) COMMENT 'Users last name',
    middle_name VARCHAR(255) COMMENT 'Users middle name',
    nickname VARCHAR(255) COMMENT 'Users preferred name',
    profile_url TEXT COMMENT 'Link to users profile page',
    picture_url TEXT COMMENT 'Link to users profile picture',
    website_url TEXT COMMENT 'Link to users website',
    gender VARCHAR(20) COMMENT 'Users gender (male, female, other)',
    birthdate DATE COMMENT 'Users birth date',
    zoneinfo VARCHAR(100) COMMENT 'Time zone (e.g., America/New_York)',
    locale VARCHAR(10) COMMENT 'Locale code (e.g., en-US)',
    phone_number VARCHAR(20) COMMENT 'Phone number with international prefix',
    phone_number_verified BOOLEAN DEFAULT FALSE COMMENT 'Phone number verification status',
    address JSON COMMENT 'Full address as JSON object',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user_id (user_id),
    KEY idx_email (email),
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User identity information for OpenID Connect UserInfo endpoint';

-- ============================================================================
-- USER CONSENTS TABLE
-- ============================================================================
-- Records user consent to share specific scopes with applications
-- Prevents repeated consent screens for same app+scope combination

CREATE TABLE IF NOT EXISTS oauth2_user_consents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) NOT NULL COMMENT 'User who gave consent',
    client_id VARCHAR(255) NOT NULL COMMENT 'Application receiving scopes',
    scopes JSON NOT NULL COMMENT 'Scopes user approved as JSON array',
    ip_address VARCHAR(45) COMMENT 'IP address when consent given',
    user_agent TEXT COMMENT 'Browser user agent when consent given',
    consented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'When consent expires (optional)',
    revoked_at TIMESTAMP NULL COMMENT 'When user revoked consent',
    
    KEY idx_user_id (user_id),
    KEY idx_client_id (client_id),
    KEY idx_user_client (user_id, client_id),
    FOREIGN KEY (client_id) REFERENCES oauth2_clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track user consent for OAuth2 scope permissions';

-- ============================================================================
-- MIGRATIONS INDEX
-- ============================================================================
-- Track which migrations have been applied

CREATE TABLE IF NOT EXISTS oauth2_migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    migration_name VARCHAR(255) UNIQUE NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track applied OAuth2 database migrations';

-- Mark this migration as applied
INSERT INTO oauth2_migrations (migration_name, batch) 
VALUES ('migration_20260403_001_authorization_code_flow', 1)
ON DUPLICATE KEY UPDATE executed_at = CURRENT_TIMESTAMP;
