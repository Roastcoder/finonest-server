<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update existing slides with correct image URLs
    $updates = [
        ['/assets/hero-home-loan.jpg', 'https://finonest.com/assets/hero-home-loan.jpg'],
        ['/assets/hero-car-loan.jpg', 'https://finonest.com/assets/hero-car-loan.jpg'],
        ['/assets/hero-business-loan.jpg', 'https://finonest.com/assets/hero-business-loan.jpg']
    ];
    
    foreach ($updates as $update) {
        $stmt = $pdo->prepare("UPDATE slides SET image_url = ? WHERE image_url = ?");
        $stmt->execute([$update[1], $update[0]]);
        echo "Updated {$update[0]} to {$update[1]}\n";
    }
    
    echo "Slide image URLs updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>