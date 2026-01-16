<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up database tables...\n";
    
    // Read and execute schema
    $schema = file_get_contents('schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "Database setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>