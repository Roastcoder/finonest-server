<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // 1. System Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 2. Loan Applications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS loan_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL,
        pan VARCHAR(10),
        pan_name VARCHAR(255),
        pan_response JSON,
        dob VARCHAR(20),
        gender VARCHAR(10),
        credit_score INT,
        credit_response JSON,
        vehicle_rc VARCHAR(20),
        vehicle_model VARCHAR(255),
        vehicle_year INT,
        vehicle_make VARCHAR(255),
        owner_name VARCHAR(255),
        fuel_type VARCHAR(50),
        vehicle_color VARCHAR(50),
        vehicle_response JSON,
        vehicle_value INT,
        income INT,
        employment VARCHAR(100),
        application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // 3. Lender Policies Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS lender_policies (
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
    )");
    
    // Insert default settings
    $defaultSettings = [
        ['razorpay_key', 'rzp_test_default', 'Razorpay API Key for payments'],
        ['razorpay_secret', '', 'Razorpay Secret Key (keep secure)'],
        ['site_name', 'Finonest', 'Website name'],
        ['contact_email', 'info@finonest.com', 'Contact email address'],
        ['gemini_api_key', '', 'Google Gemini API key for AI features'],
        ['gemini_model', 'gemini-2.5-flash-lite', 'Gemini model for AI operations'],
        ['ai_enabled', 'enabled', 'Enable or disable AI features'],
        ['pan_api_url', 'https://profilex-api.neokred.tech/core-svc/api/v2/exp/validation-service/pan-premium', 'PAN verification API URL'],
        ['pan_client_user_id', '', 'PAN API client user ID'],
        ['pan_secret_key', '', 'PAN API secret key'],
        ['pan_access_key', '', 'PAN API access key'],
        ['credit_api_url', 'https://profilex-api.neokred.tech/core-svc/api/v2/exp/user-profiling/credit-report', 'Credit report API URL'],
        ['credit_client_user_id', '', 'Credit API client user ID'],
        ['credit_secret_key', '', 'Credit API secret key'],
        ['credit_access_key', '', 'Credit API access key'],
        ['credit_service_id', '', 'Credit API service ID'],
        ['client_hash_id', '', 'Client Hash ID for API authentication'],
        ['base_url', 'https://profilex-api.neokred.tech/core-svc/api/v2/exp', 'Base URL for API calls'],
        ['secret_key', '', 'Secret key for API authentication'],
        ['access_key', '', 'Access key for API authentication'],
        ['service_id', '', 'Service ID for Credit API'],
        ['surepass_api_url', 'https://kyc-api.surepass.io/api/v1/rc/rc-full', 'SurePass RC API URL'],
        ['surepass_token', '', 'SurePass API token']
    ];
    
    foreach ($defaultSettings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    }
    
    // Insert sample lender policies
    $policies = [
        ['HDFC Bank', 'Car Loan', 'ALL', 1, 1, 1, 650, 0, 12, 84, 1, 1, 1, 1, 1, 85, 80, 1, 1, 21, 65, 25000, 8.5, 9.5],
        ['ICICI Bank', 'Auto Loan', 'ALL', 1, 1, 0, 700, 0, 12, 72, 1, 1, 0, 1, 1, 80, 75, 1, 1, 23, 60, 30000, 8.75, 10.0],
        ['Axis Bank', 'Vehicle Loan', 'ALL', 1, 0, 1, 675, 1, 24, 84, 1, 1, 1, 0, 1, 90, 85, 1, 1, 21, 65, 20000, 9.0, 11.0]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO lender_policies (lender_name, product_name, state, purchase_allowed, refinance_allowed, balance_transfer_allowed, min_cibil_score, max_dpd_allowed, min_tenure_months, max_tenure_months, petrol_allowed, diesel_allowed, cng_allowed, electric_allowed, income_proof_required, max_ltv_purchase, max_ltv_refinance, salaried_allowed, self_employed_allowed, min_age, max_age, min_income, roi_min, roi_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($policies as $policy) {
        $stmt->execute($policy);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed successfully',
        'tables' => ['system_settings', 'loan_applications', 'lender_policies']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database setup failed',
        'error' => $e->getMessage()
    ]);
}
?>