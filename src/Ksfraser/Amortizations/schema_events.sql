-- Table for out-of-schedule loan events (skipped/extra payments)
CREATE TABLE loan_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    event_type VARCHAR(16) NOT NULL, -- 'skip' or 'extra'
    event_date DATE NOT NULL,
    amount DECIMAL(16,2) DEFAULT 0.00,
    notes VARCHAR(255) DEFAULT '',
    FOREIGN KEY (loan_id) REFERENCES fa_loans(id)
);
