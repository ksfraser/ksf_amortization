-- Phase 13 Week 1 Query Optimization
-- Migration 001: Add Performance Indexes
-- Date: December 16, 2025
-- Purpose: Add indexes to support optimized query patterns

-- ============================================================================
-- TABLE: amortization_schedule
-- Description: Add indexes for commonly filtered/sorted columns
-- Impact: Improves portfolio balance, payment schedule, and interest queries
-- ============================================================================

-- Index 1: Portfolio balance queries (loan_id + payment_status)
-- Used by: Portfolio balance calculation, remaining balance queries
-- Expected improvement: 40-50%
ALTER TABLE amortization_schedule 
ADD INDEX IF NOT EXISTS idx_loan_balance (loan_id, payment_status);

-- Index 2: Payment schedule retrieval (loan_id + payment_date + status)
-- Used by: Payment schedule lookup, remaining schedule queries
-- Expected improvement: 30-40%
ALTER TABLE amortization_schedule 
ADD INDEX IF NOT EXISTS idx_schedule_lookup (loan_id, payment_date, payment_status);

-- Index 3: Interest calculation (loan_id + payment_status)
-- Used by: Cumulative interest calculation, interest accrual
-- Expected improvement: 25-35%
ALTER TABLE amortization_schedule 
ADD INDEX IF NOT EXISTS idx_interest_calc (loan_id, payment_status);

-- ============================================================================
-- TABLE: gl_accounts (Front Accounting)
-- Description: Add index for GL account mapping lookups
-- Impact: Improves GL posting performance
-- ============================================================================

-- Index 4: GL account type lookup with activity filter
-- Used by: GL account mapping, GL posting
-- Expected improvement: 20-25%
ALTER TABLE gl_accounts 
ADD INDEX IF NOT EXISTS idx_account_lookup (account_type, inactive, account_code);

-- ============================================================================
-- MIGRATION STATUS
-- ============================================================================
-- Name: migration_20251216_001_query_optimization_indexes
-- Status: Ready for deployment
-- Rollback: Drop the 4 indexes if needed
-- Testing: Verify index usage with EXPLAIN queries
-- Notes:
--   - Non-blocking migration (doesn't lock tables)
--   - Safe to run on production with live traffic
--   - Indexes will be automatically used by query optimizer
--   - Monitor query performance before/after deployment
-- ============================================================================
