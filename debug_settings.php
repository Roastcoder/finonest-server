<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        die('Database connection failed');
    }
    
    echo "Database connected successfully\n";
    
    // Check if system_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() == 0) {
        echo "ERROR: system_settings table does not exist\n";
        
        // Create the table
        $createTable = "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
        echo "Created system_settings table\n";
    } else {
        echo "system_settings table exists\n";
    }
    
    // Check current settings
    $stmt = $pdo->query("SELECT * FROM system_settings");
    $settings = $stmt->fetchAll();
    
    echo "Current settings:\n";
    foreach ($settings as $setting) {
        echo "Key: " . $setting['setting_key'] . " = " . $setting['setting_value'] . "\n";
    }
    
    // Check specifically for surepass_token
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute(['surepass_token']);
    $token = $stmt->fetchColumn();
    
    if ($token) {
        echo "SurePass token found: " . substr($token, 0, 10) . "...\n";
    } else {
        echo "SurePass token NOT found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>