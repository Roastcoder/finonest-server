<?php
// Test upload functionality
echo "Testing upload directory...\n";

$uploadDir = __DIR__ . '/uploads/blog-images/';
echo "Upload directory: $uploadDir\n";

if (!is_dir($uploadDir)) {
    echo "Creating upload directory...\n";
    mkdir($uploadDir, 0755, true);
}

if (is_writable($uploadDir)) {
    echo "✓ Upload directory is writable\n";
} else {
    echo "✗ Upload directory is not writable\n";
    echo "Fixing permissions...\n";
    chmod($uploadDir, 0755);
}

// Test file creation
$testFile = $uploadDir . 'test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✓ Can create files in upload directory\n";
    unlink($testFile);
} else {
    echo "✗ Cannot create files in upload directory\n";
}

echo "Upload test completed.\n";
?>