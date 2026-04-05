-- Migration: 2026-04-04 - Authorization Code Query Optimization
-- Purpose: Add database indexes to optimize authorization code lookup queries
--
-- This migration adds indexes on frequently queried columns to reduce
-- query execution time for authorization code retrieval and validation.
--
-- Indexes:
-- 1. idx_code - Fast lookup by authorization code (primary search key)
-- 2. idx_client_redirect - Query by client_id + redirect_uri combination
-- 3. idx_expires_at - Fast cleanup of expired codes
-- 4. idx_user_id - Lookup codes by user for revocation
--
-- Expected Impact:
-- - getCode() queries: ~200ms -> ~5-10ms (40x faster)
-- - Expiration cleanup: ~500ms -> ~50ms (10x faster)
-- - Index size: ~150KB per 10,000 records

-- MySQL/MariaDB Syntax
CREATE INDEX idx_code ON oauth2_authorization_codes (code);
CREATE INDEX idx_client_redirect ON oauth2_authorization_codes (client_id, redirect_uri);
CREATE INDEX idx_expires_at ON oauth2_authorization_codes (expires_at);
CREATE INDEX idx_user_id ON oauth2_authorization_codes (user_id);
CREATE INDEX idx_used_at ON oauth2_authorization_codes (used_at);
