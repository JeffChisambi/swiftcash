-- SwiftCash Solutions Database Setup
CREATE DATABASE IF NOT EXISTS swiftcash_loans;
USE swiftcash_loans;

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255),
    address TEXT NOT NULL,
    employment_status VARCHAR(100),
    monthly_income DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Loans table
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    loan_amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    loan_term_days INT NOT NULL,
    total_payable DECIMAL(15,2) NOT NULL,
    purpose VARCHAR(255),
    status ENUM('pending', 'approved', 'active', 'completed', 'overdue', 'defaulted') DEFAULT 'pending',
    disbursement_date DATE,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(100),
    reference_number VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

-- Email reminders table
CREATE TABLE email_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    reminder_type ENUM('upcoming', 'overdue', 'late_fee') NOT NULL,
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_status ENUM('sent', 'failed') DEFAULT 'sent',
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'agent') DEFAULT 'agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, full_name, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@swiftcash.mw', 'admin');

-- Sample data
INSERT INTO customers (full_name, phone_number, email, address, employment_status, monthly_income) VALUES
('Grace Mwale', '+265986001234', 'grace@email.com', 'Area 3, Lilongwe', 'employed', 150000.00),
('James Banda', '+265987005678', 'james@email.com', 'Ndirande, Blantyre', 'self-employed', 200000.00),
('Mary Phiri', '+265988009012', 'mary@email.com', 'Mzimba, Mzuzu', 'business-owner', 180000.00);

INSERT INTO loans (customer_id, loan_amount, interest_rate, loan_term_days, total_payable, purpose, status, disbursement_date, due_date) VALUES
(1, 50000.00, 23.00, 7, 61500.00, 'business', 'active', '2024-01-15', '2024-01-22'),
(2, 100000.00, 30.00, 14, 130000.00, 'emergency', 'completed', '2024-01-10', '2024-01-24'),
(3, 75000.00, 35.00, 21, 101250.00, 'education', 'active', '2024-01-18', '2024-02-08');