-- Table for loan types
CREATE TABLE ksf_amort_loan_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description VARCHAR(255) DEFAULT ''
);

-- Table for interest calculation frequencies
CREATE TABLE ksf_amort_interest_calc_frequencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description VARCHAR(255) DEFAULT ''
);

-- Prepopulate loan_types
INSERT INTO ksf_amort_loan_types (name, description) VALUES
('Auto', 'Auto loan'),
('Mortgage', 'Mortgage loan'),
('Other', 'Other loan type');

-- Prepopulate interest_calc_frequencies
INSERT INTO ksf_amort_interest_calc_frequencies (name, description) VALUES
('daily', 'Daily'),
('weekly', 'Weekly'),
('bi-weekly', 'Bi-Weekly'),
('semi-monthly', 'Semi-Monthly'),
('monthly', 'Monthly'),
('semi-annual', 'Semi-Annual'),
('annual', 'Annual');
