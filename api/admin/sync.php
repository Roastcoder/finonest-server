<?php
require_once __DIR__ . '/../../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';

function requireAdmin() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    $decoded = JWT::decode($token);
    if (!$decoded || $decoded['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }

    return $decoded;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        syncExternalData();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function syncExternalData() {
    global $db;
    
    requireAdmin();
    
    try {
        // Sync products from external API
        $productsResponse = file_get_contents('https://yoursite.com/api/products', false, stream_context_create([
            'http' => [
                'header' => "X-API-Key: lms_8188272ffd90118df860b5e768fe6681"
            ]
        ]));
        
        if ($productsResponse) {
            $productsData = json_decode($productsResponse, true);
            if ($productsData && $productsData['status'] === 200) {
                // Save products to local database
                $insertProduct = "INSERT INTO products (id, name, category, variant, commission_rate, card_image, variant_image, product_highlights, bank_redirect_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name), category=VALUES(category), variant=VALUES(variant), commission_rate=VALUES(commission_rate), card_image=VALUES(card_image), variant_image=VALUES(variant_image), product_highlights=VALUES(product_highlights), bank_redirect_url=VALUES(bank_redirect_url), updated_at=NOW()";
                $insertStmt = $db->prepare($insertProduct);
                
                foreach ($productsData['data'] as $product) {
                    $insertStmt->execute([
                        $product['id'],
                        $product['name'],
                        $product['category'],
                        $product['variant'],
                        $product['commission_rate'],
                        $product['card_image'],
                        $product['variant_image'],
                        $product['product_highlights'],
                        $product['bank_redirect_url']
                    ]);
                }
            }
        }
        
        // Sync leads from external API (if available)
        $leadsResponse = file_get_contents('https://yoursite.com/api/leads', false, stream_context_create([
            'http' => [
                'header' => "X-API-Key: lms_8188272ffd90118df860b5e768fe6681"
            ]
        ]));
        
        if ($leadsResponse) {
            $leadsData = json_decode($leadsResponse, true);
            if ($leadsData && isset($leadsData['data'])) {
                // Save leads to local database
                $insertLead = "INSERT INTO leads (external_id, name, mobile, email, product_id, product_name, product_variant, product_highlights, bank_redirect_url, channel_code, status, source, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'External API', NOW(), NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status), updated_at=NOW()";
                $insertStmt = $db->prepare($insertLead);
                
                foreach ($leadsData['data'] as $lead) {
                    // Get product details
                    $productQuery = "SELECT name, variant, product_highlights, bank_redirect_url FROM products WHERE id = ?";
                    $productStmt = $db->prepare($productQuery);
                    $productStmt->execute([$lead['product_id']]);
                    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $insertStmt->execute([
                        $lead['id'] ?? null,
                        $lead['name'],
                        $lead['mobile'],
                        $lead['email'],
                        $lead['product_id'],
                        $product['name'] ?? null,
                        $product['variant'] ?? null,
                        $product['product_highlights'] ?? null,
                        $product['bank_redirect_url'] ?? null,
                        $lead['channel_code'] ?? 'PARTNER_001',
                        $lead['status'] ?? 'new'
                    ]);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'External data synced successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to sync external data: ' . $e->getMessage()]);
    }
}
?>