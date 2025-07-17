-- Table: fa_loans
CREATE TABLE fa_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    principal DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    term_months INT NOT NULL,
    repayment_schedule VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table: fa_amortization_staging
CREATE TABLE fa_amortization_staging (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(15,2) NOT NULL,
    principal_portion DECIMAL(15,2) NOT NULL,
    interest_portion DECIMAL(15,2) NOT NULL,
    remaining_balance DECIMAL(15,2) NOT NULL,
    posted_to_gl TINYINT(1) DEFAULT 0,
    posted_at DATETIME,
    trans_no INT,
    trans_type VARCHAR(20),
    FOREIGN KEY (loan_id) REFERENCES fa_loans(id)
);
