-- Table: fa_loans
CREATE TABLE 0_ksf_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_id INT NOT NULL,
    amount_financed DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    num_payments INT NOT NULL,
    first_payment_date DATE NOT NULL,
    regular_payment DECIMAL(15,2) NOT NULL,
    override_payment TINYINT(1) DEFAULT 0,
    loan_type VARCHAR(32),
    interest_calc_frequency VARCHAR(32),
    status VARCHAR(16) DEFAULT 'active'
);

-- Table: fa_amortization_staging
CREATE TABLE 0_ksf_amortization_staging (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(15,2) NOT NULL,
    principal_portion DECIMAL(15,2) NOT NULL,
    interest_portion DECIMAL(15,2) NOT NULL,
    remaining_balance DECIMAL(15,2) NOT NULL,
    posted_to_gl TINYINT(1) DEFAULT 0,
    trans_no INT,
    trans_type INT,
    voided TINYINT(1) DEFAULT 0,
    FOREIGN KEY (loan_id) REFERENCES fa_loans(id)
);
