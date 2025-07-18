CREATE TABLE 0_ksf_amort_loan_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL,
    description VARCHAR(128)
);

CREATE TABLE 0_ksf_amort_interest_calc_frequencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL,
    description VARCHAR(128)
);

-- Prepopulate loan_types
INSERT INTO ksf_amort_loan_types (name, description) VALUES
    ('auto', 'Auto loan'),
    ('mortgage', 'Mortgage loan'),
    ('personal', 'Personal loan'),
    ('student', 'Student loan');

-- Prepopulate interest_calc_frequencies
INSERT INTO ksf_amort_interest_calc_frequencies (name, description) VALUES
    ('monthly', 'Monthly'),
    ('annual', 'Annual'),
    ('daily', 'Daily'),
    ('semi-annual', 'Semi-annual');
