<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../middleware/cors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();
    
    // Create lender policies table
    $createTable = "CREATE TABLE IF NOT EXISTS lender_policies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lender_name VARCHAR(255) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        state VARCHAR(100),
        purchase_allowed BOOLEAN DEFAULT FALSE,
        refinance_allowed BOOLEAN DEFAULT FALSE,
        balance_transfer_allowed BOOLEAN DEFAULT FALSE,
        min_cibil_score INT DEFAULT 0,
        max_dpd_allowed INT DEFAULT 0,
        min_tenure_months INT DEFAULT 12,
        max_tenure_months INT DEFAULT 84,
        petrol_allowed BOOLEAN DEFAULT FALSE,
        diesel_allowed BOOLEAN DEFAULT FALSE,
        cng_allowed BOOLEAN DEFAULT FALSE,
        electric_allowed BOOLEAN DEFAULT FALSE,
        income_proof_required BOOLEAN DEFAULT TRUE,
        max_ltv_purchase DECIMAL(5,2) DEFAULT 0,
        max_ltv_refinance DECIMAL(5,2) DEFAULT 0,
        salaried_allowed BOOLEAN DEFAULT FALSE,
        self_employed_allowed BOOLEAN DEFAULT FALSE,
        min_age INT DEFAULT 21,
        max_age INT DEFAULT 65,
        min_income INT DEFAULT 0,
        roi_min DECIMAL(5,2) DEFAULT 8.5,
        roi_max DECIMAL(5,2) DEFAULT 15.0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTable);
    
    // Insert sample policies if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM lender_policies")->fetchColumn();
    if ($count == 0) {
        $policies = [
            ['HDFC Bank', 'Car Loan', 'ALL', 1, 1, 1, 650, 0, 12, 84, 1, 1, 1, 1, 1, 85, 80, 1, 1, 21, 65, 25000, 8.5, 9.5],
            ['ICICI Bank', 'Auto Loan', 'ALL', 1, 1, 0, 700, 0, 12, 72, 1, 1, 0, 1, 1, 80, 75, 1, 1, 23, 60, 30000, 8.75, 10.0],
            ['Axis Bank', 'Vehicle Loan', 'ALL', 1, 0, 1, 675, 1, 24, 84, 1, 1, 1, 0, 1, 90, 85, 1, 1, 21, 65, 20000, 9.0, 11.0]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO lender_policies (lender_name, product_name, state, purchase_allowed, refinance_allowed, balance_transfer_allowed, min_cibil_score, max_dpd_allowed, min_tenure_months, max_tenure_months, petrol_allowed, diesel_allowed, cng_allowed, electric_allowed, income_proof_required, max_ltv_purchase, max_ltv_refinance, salaried_allowed, self_employed_allowed, min_age, max_age, min_income, roi_min, roi_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($policies as $policy) {
            $stmt->execute($policy);
        }
    }
    
    // Policy matching logic
    $creditScore = $input['creditScore'] ?? 0;
    $fuelType = strtolower($input['fuelType'] ?? 'petrol');
    $employment = $input['employment'] ?? 'salaried';
    $income = $input['income'] ?? 0;
    $loanType = $input['loanType'] ?? 'purchase';
    
    $query = "SELECT * FROM lender_policies WHERE 
        min_cibil_score <= ? AND
        min_income <= ? AND
        " . $loanType . "_allowed = 1 AND
        " . ($employment === 'salaried' ? 'salaried_allowed' : 'self_employed_allowed') . " = 1 AND
        " . $fuelType . "_allowed = 1
        ORDER BY roi_min ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$creditScore, $income]);
    $eligibleProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'eligible_products' => $eligibleProducts,
        'total_products' => count($eligibleProducts)
    ]);
    
} catch (Exception $e) {
    error_log('Policy Engine Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Policy matching failed', 'error' => $e->getMessage()]);
}
?>