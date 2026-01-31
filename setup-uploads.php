<?php
// Create upload directories
$uploadDirs = [
    __DIR__ . '/uploads/job-images/',
    __DIR__ . '/uploads/cvs/'
];

foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        echo "Directory already exists: $dir\n";
    }
    
    // Check permissions
    if (is_writable($dir)) {
        echo "Directory is writable: $dir\n";
    } else {
        echo "Directory is NOT writable: $dir\n";
        // Try to fix permissions
        if (chmod($dir, 0755)) {
            echo "Fixed permissions for: $dir\n";
        }
    }
}

echo "Upload directories setup complete.\n";
?>