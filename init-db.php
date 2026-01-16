<?php
ob_start();
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Database initialized successfully']);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>