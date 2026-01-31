<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create branches table if it doesn't exist
    $createTable = "
    CREATE TABLE IF NOT EXISTS branches (
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
    )";
    
    $db->exec($createTable);
    echo "Branches table created successfully.\n";
    
    // Check if data already exists
    $checkData = $db->query("SELECT COUNT(*) FROM branches")->fetchColumn();
    
    if ($checkData == 0) {
        // Insert demo data
        $insertData = "
        INSERT INTO branches (name, address, city, state, pincode, phone, email, latitude, longitude, manager_name) VALUES 
        ('Finonest Mumbai Central', '123 Business District, Nariman Point', 'Mumbai', 'Maharashtra', '400001', '+91-22-12345678', 'mumbai@finonest.com', 18.9220, 72.8347, 'Rajesh Kumar'),
        ('Finonest Delhi Branch', '456 Connaught Place, Central Delhi', 'New Delhi', 'Delhi', '110001', '+91-11-87654321', 'delhi@finonest.com', 28.6315, 77.2167, 'Priya Sharma'),
        ('Finonest Bangalore Tech Hub', '789 MG Road, Brigade Road', 'Bangalore', 'Karnataka', '560001', '+91-80-11223344', 'bangalore@finonest.com', 12.9716, 77.5946, 'Suresh Reddy'),
        ('Finonest Chennai Branch', '101 Anna Salai, T. Nagar', 'Chennai', 'Tamil Nadu', '600017', '+91-44-55667788', 'chennai@finonest.com', 13.0827, 80.2707, 'Lakshmi Iyer'),
        ('Finonest Pune Office', '202 FC Road, Shivajinagar', 'Pune', 'Maharashtra', '411005', '+91-20-99887766', 'pune@finonest.com', 18.5204, 73.8567, 'Amit Patil'),
        ('Finonest Hyderabad Center', '303 Banjara Hills, Road No. 12', 'Hyderabad', 'Telangana', '500034', '+91-40-44332211', 'hyderabad@finonest.com', 17.3850, 78.4867, 'Venkat Rao'),
        ('Finonest Kolkata Office', '404 Park Street, Central Kolkata', 'Kolkata', 'West Bengal', '700016', '+91-33-22334455', 'kolkata@finonest.com', 22.5726, 88.3639, 'Anita Das'),
        ('Finonest Ahmedabad Branch', '505 CG Road, Navrangpura', 'Ahmedabad', 'Gujarat', '380009', '+91-79-66778899', 'ahmedabad@finonest.com', 23.0225, 72.5714, 'Kiran Patel')
        ";
        
        $db->exec($insertData);
        echo "Demo data inserted successfully.\n";
        echo "Total branches created: 8\n";
    } else {
        echo "Branches table already has data ($checkData records).\n";
    }
    
    echo "Initialization completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>