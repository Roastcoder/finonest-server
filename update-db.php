<?php
header('Content-Type: text/plain');

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Updating database tables...\n";
    
    // Make latitude and longitude nullable in territories table
    $alterQuery = "ALTER TABLE territories 
                   MODIFY COLUMN latitude DECIMAL(10,8) NULL,
                   MODIFY COLUMN longitude DECIMAL(11,8) NULL";
    
    $db->exec($alterQuery);
    echo "✓ Updated territories table - latitude/longitude now nullable\n";
    
    // Ensure all banker tables exist with correct structure
    $tables = [
        "CREATE TABLE IF NOT EXISTS lenders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) UNIQUE NOT NULL,
            email_domain VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS bankers (
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
        )",
        "CREATE TABLE IF NOT EXISTS case_types (
            id INT PRIMARY KEY AUTO_INCREMENT,
            territory_id INT NOT NULL,
            type ENUM('sale-purchase', 'normal-refinance', 'balance-transfer') NOT NULL,
            remarks TEXT,
            loan_capping DECIMAL(15,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (territory_id) REFERENCES territories(id) ON DELETE CASCADE
        )"
    ];
    
    foreach ($tables as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Insert default lenders if not exists
    $lenders = [
        ['HDFC Bank', 'hdfc', '@hdfcbank.com'],
        ['ICICI Bank', 'icici', '@icicibank.com'],
        ['State Bank of India', 'sbi', '@sbi.co.in'],
        ['Axis Bank', 'axis', '@axisbank.com'],
        ['Kotak Mahindra Bank', 'kotak', '@kotak.com']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO lenders (name, code, email_domain) VALUES (?, ?, ?)");
    foreach ($lenders as $lender) {
        $stmt->execute($lender);
    }
    
    echo "✓ Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>