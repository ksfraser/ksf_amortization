CREATE TABLE 0_ksf_loan_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    event_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (loan_id) REFERENCES fa_loans(id)
);
