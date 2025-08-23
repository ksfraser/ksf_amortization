
-- Table: 0_ksf_selectors
CREATE TABLE 0_ksf_selectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selector_name VARCHAR(32) NOT NULL,
    option_name VARCHAR(64) NOT NULL,
    option_value VARCHAR(64) NOT NULL
);

-- Pre-populate with current values
INSERT INTO 0_ksf_selectors (selector_name, option_name, option_value) VALUES
    ('payment_frequency', 'Annual', 'annual'),
    ('payment_frequency', 'Semi-Annual', 'semi-annual'),
    ('payment_frequency', 'Monthly', 'monthly'),
    ('payment_frequency', 'Semi-Monthly', 'semi-monthly'),
    ('payment_frequency', 'Bi-Weekly', 'bi-weekly'),
    ('payment_frequency', 'Weekly', 'weekly'),
    ('borrower_type', 'Customer', 'Customer'),
    ('borrower_type', 'Supplier', 'Supplier'),
    ('borrower_type', 'Employee', 'Employee');
