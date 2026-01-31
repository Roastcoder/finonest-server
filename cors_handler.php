<?php
require_once __DIR__ . '/middleware/cors_secure.php';

// Handle CORS preflight
SecureCorsMiddleware::handle();
?>