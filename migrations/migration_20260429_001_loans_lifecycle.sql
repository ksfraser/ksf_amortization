-- Phase 2: Loan Lifecycle Management - Database Schema
-- Migration: 2026-04-29_001_create_loans_table

CREATE TABLE IF NOT EXISTS `borrowers` (
  `borrower_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint unsigned,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255),
  `phone` varchar(20),
  `ssn` varchar(11),
  `date_of_birth` date,
  `address` varchar(255),
  `city` varchar(100),
  `state` varchar(2),
  `zip_code` varchar(10),
  `employment_status` enum('employed','self_employed','unemployed','retired'),
  `annual_income` decimal(12, 2),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY `unique_ssn` (`ssn`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Main loans table
CREATE TABLE IF NOT EXISTS `loans` (
  `loan_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_number` varchar(20) NOT NULL UNIQUE,
  `borrower_id` bigint unsigned NOT NULL,
  `loan_officer_id` bigint unsigned,
  
  -- Loan terms
  `loan_type` enum('personal','auto','business','mortgage') NOT NULL,
  `purpose` varchar(255),
  `original_amount` decimal(15, 2) NOT NULL,
  `current_balance` decimal(15, 2) NOT NULL,
  `interest_rate` decimal(6, 4) NOT NULL,
  `term_months` int unsigned NOT NULL,
  `monthly_payment` decimal(15, 2) NOT NULL,
  
  -- Dates
  `origination_date` date,
  `funding_date` date,
  `first_payment_date` date,
  `next_due_date` date,
  `maturity_date` date,
  
  -- Status tracking
  `stage` enum('ORIGINATION','PENDING','ACTIVE','PAID_OFF','CHARGED_OFF','DEFAULTED') NOT NULL DEFAULT 'ORIGINATION',
  `status` enum('CURRENT','DELINQUENT_30','DELINQUENT_60','DELINQUENT_90+','PAID_OFF','CHARGED_OFF') NOT NULL DEFAULT 'CURRENT',
  
  -- Performance metrics
  `days_past_due` int unsigned DEFAULT 0,
  `past_due_amount` decimal(15, 2) DEFAULT 0,
  `total_paid` decimal(15, 2) DEFAULT 0,
  `total_interest_paid` decimal(15, 2) DEFAULT 0,
  `last_payment_date` date,
  `payment_count` int unsigned DEFAULT 0,
  
  -- Interest accrual tracking
  `accrued_interest` decimal(15, 2) DEFAULT 0,
  `last_interest_accrual_date` date,
  
  -- Approval & documentation
  `approval_date` date,
  `approved_by` bigint unsigned,
  `approval_notes` text,
  `credit_score_at_origination` int,
  `ltv_ratio` decimal(5, 2),
  
  -- Insurance & fees
  `insurance_amount` decimal(12, 2) DEFAULT 0,
  `origination_fee` decimal(12, 2) DEFAULT 0,
  `late_fee_schedule` varchar(50),
  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`borrower_id`) REFERENCES `borrowers`(`borrower_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`loan_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `unique_loan_number` (`loan_number`),
  KEY `idx_borrower` (`borrower_id`),
  KEY `idx_status` (`status`),
  KEY `idx_stage` (`stage`),
  KEY `idx_next_due_date` (`next_due_date`),
  KEY `idx_maturity_date` (`maturity_date`),
  KEY `idx_created` (`created_at`),
  KEY `idx_officer` (`loan_officer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Amortization schedule
CREATE TABLE IF NOT EXISTS `amortization_schedules` (
  `schedule_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_id` bigint unsigned NOT NULL,
  `period_number` int unsigned NOT NULL,
  `due_date` date NOT NULL,
  `payment_amount` decimal(15, 2) NOT NULL,
  `principal_payment` decimal(15, 2) NOT NULL,
  `interest_payment` decimal(15, 2) NOT NULL,
  `ending_balance` decimal(15, 2) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `paid_date` date,
  `actual_payment` decimal(15, 2),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`loan_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_schedule` (`loan_id`, `period_number`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_is_paid` (`is_paid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments received
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_id` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount_received` decimal(15, 2) NOT NULL,
  `principal_portion` decimal(15, 2) DEFAULT 0,
  `interest_portion` decimal(15, 2) DEFAULT 0,
  `fee_portion` decimal(15, 2) DEFAULT 0,
  `payment_method` enum('ach','card','wire','check','cash') NOT NULL,
  `reference_number` varchar(50),
  `payer_name` varchar(255),
  `status` enum('pending','posted','failed','reversed') NOT NULL DEFAULT 'pending',
  `posted_date` date,
  `notes` text,
  `created_by` bigint unsigned,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`loan_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_status` (`status`),
  KEY `idx_posted_date` (`posted_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interest accrual tracking (for daily calculation validation)
CREATE TABLE IF NOT EXISTS `interest_accruals` (
  `accrual_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_id` bigint unsigned NOT NULL,
  `accrual_date` date NOT NULL,
  `balance` decimal(15, 2) NOT NULL,
  `daily_interest` decimal(12, 4) NOT NULL,
  `cumulative_interest` decimal(15, 2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`loan_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_accrual` (`loan_id`, `accrual_date`),
  KEY `idx_accrual_date` (`accrual_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loan status history (audit trail)
CREATE TABLE IF NOT EXISTS `loan_status_history` (
  `history_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_id` bigint unsigned NOT NULL,
  `old_status` varchar(50),
  `new_status` varchar(50) NOT NULL,
  `reason` text,
  `changed_by` bigint unsigned,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`loan_id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Underwriting & approvals
CREATE TABLE IF NOT EXISTS `loan_approvals` (
  `approval_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `loan_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `approval_status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `comments` text,
  `risk_score` decimal(5, 2),
  `condition_count` int DEFAULT 0,
  `approval_date` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`loan_id`) ON DELETE CASCADE,
  FOREIGN KEY (`approver_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance indexes for high-volume queries
CREATE INDEX idx_current_status ON loans(status, created_at);
CREATE INDEX idx_balance_search ON loans(current_balance, status);
CREATE INDEX idx_payment_schedule ON loans(next_due_date, status);
CREATE INDEX idx_approval_pending ON loan_approvals(approval_status, created_at);
