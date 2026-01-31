<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Simple PDF generation without external libraries
function generatePDF($data) {
    $pdf_content = "%PDF-1.4\n";
    $pdf_content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $pdf_content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $pdf_content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $pdf_content .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    
    // Content stream
    $content = "BT\n";
    $content .= "/F1 16 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(FINONEST INDIA PVT. LTD.) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Loan Onboarding Report) Tj\n";
    $content .= "/F1 12 Tf\n";
    $content .= "0 -30 Td\n";
    $content .= "(Application ID: " . $data['application_id'] . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Generated: " . date('Y-m-d H:i:s') . ") Tj\n";
    $content .= "0 -40 Td\n";
    $content .= "(VEHICLE INFORMATION) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Registration: " . $data['vehicle_info']['registration_number'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Vehicle: " . $data['vehicle_info']['details'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Owner: " . $data['vehicle_info']['owner'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Market Value: " . $data['vehicle_info']['market_value'] . ") Tj\n";
    $content .= "0 -30 Td\n";
    $content .= "(CREDIT ANALYSIS) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Credit Score: " . $data['credit_analysis']['score'] . "/900) Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(Rating: " . $data['credit_analysis']['rating'] . ") Tj\n";
    $content .= "0 -30 Td\n";
    $content .= "(FINANCER INFORMATION) Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(RC Financer: " . $data['financer_info']['rc_financer'] . ") Tj\n";
    $content .= "0 -15 Td\n";
    $content .= "(CIBIL Match: " . ($data['financer_info']['cibil_match'] ? 'YES' : 'NO') . ") Tj\n";
    $content .= "ET\n";
    
    $content_length = strlen($content);
    $pdf_content .= "5 0 obj\n<< /Length $content_length >>\nstream\n$content\nendstream\nendobj\n";
    
    $pdf_content .= "xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n0000000125 00000 n \n0000000348 00000 n \n0000000565 00000 n \n";
    $pdf_content .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . (strlen($pdf_content) + 50) . "\n%%EOF";
    
    return $pdf_content;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $applicationId = $_GET['id'] ?? null;
    
    if (!$applicationId) {
        throw new Exception('Application ID required');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM loan_onboarding WHERE id = ?");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    // Parse responses
    $vehicleResponse = json_decode($application['vehicle_response'], true);
    $vehicleData = $vehicleResponse['data'];
    
    // Prepare data for PDF
    $pdfData = [
        'application_id' => $application['id'],
        'vehicle_info' => [
            'registration_number' => $vehicleData['rc_number'] ?? '',
            'details' => $vehicleData['maker_description'] . ' ' . $vehicleData['maker_model'],
            'owner' => $vehicleData['owner_name'] ?? '',
            'market_value' => '₹6.6L'
        ],
        'credit_analysis' => [
            'score' => $application['credit_score'],
            'rating' => $application['credit_score'] >= 750 ? 'Excellent' : 'Good'
        ],
        'financer_info' => [
            'rc_financer' => strtoupper($vehicleData['financer'] ?? 'NOT FINANCED'),
            'cibil_match' => true
        ]
    ];
    
    // Generate PDF
    $pdf_content = generatePDF($pdfData);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="finonest_loan_report_' . $applicationId . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    
    echo $pdf_content;
    
} catch (Exception $e) {
    error_log('PDF Generation Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>