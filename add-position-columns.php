<?php
header('Content-Type: text/plain');

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding position columns to branches table...\n";
    
    // Add x_position and y_position columns if they don't exist
    $alterQuery = "ALTER TABLE branches 
                   ADD COLUMN IF NOT EXISTS x_position DECIMAL(5,2) NULL,
                   ADD COLUMN IF NOT EXISTS y_position DECIMAL(5,2) NULL";
    
    $db->exec($alterQuery);
    echo "✓ Added x_position and y_position columns to branches table\n";
    
    echo "✓ Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>