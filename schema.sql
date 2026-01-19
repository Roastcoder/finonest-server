CREATE DATABASE IF NOT EXISTS Fino;
USE Fino;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'USER') DEFAULT 'USER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Loan applications table
CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    purpose TEXT,
    income DECIMAL(15,2),
    employment VARCHAR(100),
    status ENUM('SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED') DEFAULT 'SUBMITTED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
);

-- Contact forms table
CREATE TABLE contact_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    loan_type VARCHAR(50),
    amount VARCHAR(50),
    consent_terms BOOLEAN DEFAULT FALSE,
    consent_data_processing BOOLEAN DEFAULT FALSE,
    consent_communication BOOLEAN DEFAULT FALSE,
    consent_marketing BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_loan_type (loan_type)
);

-- Sessions table for JWT tokens
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);

-- Branches table
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    manager_name VARCHAR(255),
    working_hours VARCHAR(255) DEFAULT '9:00 AM - 6:00 PM',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_status (status)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@finonest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');

-- Insert sample branches
INSERT INTO branches (name, address, city, state, pincode, phone, email, latitude, longitude, manager_name) VALUES 
('Finonest Mumbai Central', '123 Business District, Nariman Point', 'Mumbai', 'Maharashtra', '400001', '+91-22-12345678', 'mumbai@finonest.com', 18.9220, 72.8347, 'Rajesh Kumar'),
('Finonest Delhi Branch', '456 Connaught Place, Central Delhi', 'New Delhi', 'Delhi', '110001', '+91-11-87654321', 'delhi@finonest.com', 28.6315, 77.2167, 'Priya Sharma'),
('Finonest Bangalore Tech Hub', '789 MG Road, Brigade Road', 'Bangalore', 'Karnataka', '560001', '+91-80-11223344', 'bangalore@finonest.com', 12.9716, 77.5946, 'Suresh Reddy'),
('Finonest Chennai Branch', '101 Anna Salai, T. Nagar', 'Chennai', 'Tamil Nadu', '600017', '+91-44-55667788', 'chennai@finonest.com', 13.0827, 80.2707, 'Lakshmi Iyer'),
('Finonest Pune Office', '202 FC Road, Shivajinagar', 'Pune', 'Maharashtra', '411005', '+91-20-99887766', 'pune@finonest.com', 18.5204, 73.8567, 'Amit Patil'),
('Finonest Hyderabad Center', '303 Banjara Hills, Road No. 12', 'Hyderabad', 'Telangana', '500034', '+91-40-44332211', 'hyderabad@finonest.com', 17.3850, 78.4867, 'Venkat Rao'),
('Finonest Kolkata Office', '404 Park Street, Central Kolkata', 'Kolkata', 'West Bengal', '700016', '+91-33-22334455', 'kolkata@finonest.com', 22.5726, 88.3639, 'Anita Das'),
('Finonest Ahmedabad Branch', '505 CG Road, Navrangpura', 'Ahmedabad', 'Gujarat', '380009', '+91-79-66778899', 'ahmedabad@finonest.com', 23.0225, 72.5714, 'Kiran Patel');

-- Banker form tables
CREATE TABLE IF NOT EXISTS lenders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    email_domain VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bankers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lender_id INT NOT NULL,
    banker_name VARCHAR(255) NOT NULL,
    mobile_number VARCHAR(20) NOT NULL,
    official_email VARCHAR(255) NOT NULL,
    profile ENUM('sales-executive', 'sales-manager', 'cluster-sales-manager', 'area-sales-manager', 'zonal-sales-manager', 'national-sales-manager') NOT NULL,
    reporting_to INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lender_id) REFERENCES lenders(id),
    FOREIGN KEY (reporting_to) REFERENCES bankers(id)
);

CREATE TABLE IF NOT EXISTS territories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    banker_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    distance DECIMAL(10,2) NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (banker_id) REFERENCES bankers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS case_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    territory_id INT NOT NULL,
    type ENUM('sale-purchase', 'normal-refinance', 'balance-transfer') NOT NULL,
    remarks TEXT,
    loan_capping DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (territory_id) REFERENCES territories(id) ON DELETE CASCADE
);

-- Add map positions to branches
ALTER TABLE branches ADD COLUMN IF NOT EXISTS x_position DECIMAL(5,2) NULL;
ALTER TABLE branches ADD COLUMN IF NOT EXISTS y_position DECIMAL(5,2) NULL;

-- Insert default lenders
INSERT IGNORE INTO lenders (name, code, email_domain) VALUES
('HDFC Bank', 'hdfc', '@hdfcbank.com'),
('ICICI Bank', 'icici', '@icicibank.com'),
('State Bank of India', 'sbi', '@sbi.co.in'),
('Axis Bank', 'axis', '@axisbank.com'),
('Kotak Mahindra Bank', 'kotak', '@kotak.com');