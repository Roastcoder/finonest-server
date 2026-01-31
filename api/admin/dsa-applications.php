<?php
require_once __DIR__ . '/../../cors-handler.php';
require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all DSA applications
        $query = "SELECT 
            id, full_name, mobile_number, email, pan_number, city, state,
            current_profession, experience_years, business_type, monthly_income,
            preferred_products, target_monthly_cases, coverage_area, status,
            registration_date
        FROM dsa_applications 
        ORDER BY registration_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $applications = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($applications as &$app) {
            $app['preferred_products'] = json_decode($app['preferred_products'] ?? '[]', true);
        }
        
        echo json_encode([
            'success' => true,
            'applications' => $applications
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update application status
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['status'])) {
            throw new Exception('ID and status are required');
        }
        
        if (!in_array($input['status'], ['pending', 'approved', 'rejected'])) {
            throw new Exception('Invalid status');
        }
        
        $query = "UPDATE dsa_applications SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$input['status'], $input['id']]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update status');
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>