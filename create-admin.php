<?php
ob_start();
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Generate correct password hash for 'admin123'
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Delete existing admin user if exists
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute(['admin@finonest.com']);
    
    // Insert new admin user with correct hash
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute(['Admin User', 'admin@finonest.com', $password, 'ADMIN']);
    
    ob_clean();
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Admin user created successfully', 'hash' => $password]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create admin user']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>