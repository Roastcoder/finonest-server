-- Create careers tables
CREATE TABLE IF NOT EXISTS career_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') DEFAULT 'Full-time',
    salary VARCHAR(100),
    description TEXT NOT NULL,
    requirements TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS career_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    experience VARCHAR(100),
    cover_letter TEXT,
    cv_filename VARCHAR(255),
    cv_path VARCHAR(500),
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES career_jobs(id) ON DELETE CASCADE
);

-- Insert sample job data
INSERT INTO career_jobs (title, department, location, type, salary, description, requirements) VALUES
('Senior Financial Analyst', 'Finance', 'Mumbai, India', 'Full-time', '₹8-12 LPA', 'We are looking for a Senior Financial Analyst to join our growing team.', '• Bachelor''s degree in Finance\n• 3+ years experience\n• Strong analytical skills'),
('Sales Executive', 'Sales', 'Jaipur, India', 'Full-time', '₹5-8 LPA', 'Join our sales team and help customers find the right loan products.', '• Excellent communication skills\n• Sales experience preferred\n• Customer-focused approach'),
('Loan Officer', 'Operations', 'Udaipur, India', 'Full-time', '₹4-6 LPA', 'Process loan applications and ensure compliance with company policies.', '• Banking or finance background\n• Attention to detail\n• Knowledge of loan processes');