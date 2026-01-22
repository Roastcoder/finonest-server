<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/database.php';

function validateApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? null;
    
    if (!$apiKey || $apiKey !== 'lms_8188272ffd90118df860b5e768fe6681') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getProducts();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getProducts() {
    global $db;
    
    validateApiKey();
    
    try {
        // Create products table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) DEFAULT 'Credit Card',
            variant VARCHAR(255) NOT NULL,
            commission_rate DECIMAL(10,2) DEFAULT 0,
            card_image VARCHAR(500),
            variant_image VARCHAR(500),
            product_highlights TEXT,
            bank_redirect_url VARCHAR(500),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($createTable);
        
        // Insert default credit card products if table is empty
        $count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count == 0) {
            $defaultProducts = [
                [
                    'name' => 'Classic Credit Card',
                    'category' => 'Credit Card',
                    'variant' => 'IDFC First Bank',
                    'commission_rate' => 1400.00,
                    'card_image' => 'https://cards.finonest.com/assets/cards/idfc-classic.jpg',
                    'variant_image' => 'assets/cards/variant_idfc.jpg',
                    'product_highlights' => 'Lifetime Free: No joining or annual fees. Cashback on all purchases. Fuel surcharge waiver. Welcome bonus points.',
                    'bank_redirect_url' => 'https://www.idfcfirstbank.com/credit-card/classic'
                ],
                [
                    'name' => 'Premium Credit Card',
                    'category' => 'Credit Card',
                    'variant' => 'HDFC Bank',
                    'commission_rate' => 2500.00,
                    'card_image' => 'https://cards.finonest.com/assets/cards/hdfc-premium.jpg',
                    'variant_image' => 'assets/cards/variant_hdfc.jpg',
                    'product_highlights' => 'Premium benefits with airport lounge access. High reward points on dining and shopping. Complimentary insurance coverage.',
                    'bank_redirect_url' => 'https://www.hdfcbank.com/credit-card/premium'
                ],
                [
                    'name' => 'Business Credit Card',
                    'category' => 'Credit Card',
                    'variant' => 'Axis Bank',
                    'commission_rate' => 1800.00,
                    'card_image' => 'https://cards.finonest.com/assets/cards/axis-business.jpg',
                    'variant_image' => 'assets/cards/variant_axis.jpg',
                    'product_highlights' => 'Designed for business expenses. Higher credit limits. Business reward points. Expense management tools.',
                    'bank_redirect_url' => 'https://www.axisbank.com/credit-card/business'
                ]
            ];
            
            $stmt = $db->prepare("INSERT INTO products (name, category, variant, commission_rate, card_image, variant_image, product_highlights, bank_redirect_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($defaultProducts as $product) {
                $stmt->execute(array_values($product));
            }
        }
        
        $query = "SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 200,
            'message' => 'Success',
            'data' => $products
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}
?>