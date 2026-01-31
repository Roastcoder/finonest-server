<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Application.php';

function authenticate() {
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
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }

    return $decoded;
}

$database = new Database();
$db = $database->getConnection();
$application = new Application($db);

$method = $_SERVER['REQUEST_METHOD'];
$path_info = $_SERVER['PATH_INFO'] ?? '';
$request = explode('/', trim($path_info, '/'));

switch($method) {
    case 'POST':
        submitForm();
        break;
    case 'PUT':
        updateApplicationStatus();
        break;
    case 'GET':
        // Check if it's a request for user's own applications
        if (strpos($_SERVER['REQUEST_URI'], '/mine') !== false) {
            getMyForms();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function submitForm() {
    global $application, $db;
    
    $auth_user = authenticate();
    $data = json_decode(file_get_contents("php://input"), true);
    
    $form_type = $data['form_type'] ?? 'loan_application';
    
    if ($form_type === 'course_enrollment') {
        handleCourseEnrollment($auth_user, $data['form_data']);
        return;
    }
    
    // Handle regular loan applications
    $form_fields = isset($data['form_data']) ? $data['form_data'] : $data;
    
    // Create form_data object from the submitted data
    $form_data = [
        'loan_type' => $form_fields['loanType'] ?? $form_fields['loan_type'] ?? 'General Inquiry',
        'amount' => $form_fields['amount'] ?? 0,
        'full_name' => $form_fields['full_name'] ?? '',
        'email' => $form_fields['email'] ?? '',
        'phone' => $form_fields['phone'] ?? '',
        'employment_type' => $form_fields['employment'] ?? $form_fields['employment_type'] ?? '',
        'monthly_income' => $form_fields['income'] ?? $form_fields['monthly_income'] ?? 0,
        'notes' => $form_fields['purpose'] ?? $form_fields['notes'] ?? ''
    ];

    $application_id = $application->create($auth_user['user_id'], $form_data);
    
    if ($application_id) {
        echo json_encode([
            'success' => true,
            'application_id' => $application_id,
            'message' => 'Application submitted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit application']);
    }
}

function handleCourseEnrollment($auth_user, $enrollment_data) {
    global $db;
    
    try {
        // Create course_enrollments table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS course_enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            course_title VARCHAR(255) NOT NULL,
            amount_paid DECIMAL(10,2) DEFAULT 0.00,
            payment_method VARCHAR(50),
            payment_id VARCHAR(255),
            payment_details JSON,
            payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            student_info JSON,
            enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $db->exec($createTable);
        
        // Handle QR payment status
        $payment_status = 'pending';
        if ($enrollment_data['amount_paid'] == 0) {
            $payment_status = 'completed';
        } elseif (isset($enrollment_data['payment_details']['qr_payment']) && $enrollment_data['payment_details']['qr_payment']) {
            $payment_status = 'pending'; // QR payments need manual verification
        }
        
        // Insert enrollment record
        $query = "INSERT INTO course_enrollments 
                  (user_id, course_id, course_title, amount_paid, payment_method, payment_id, payment_details, student_info, payment_status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $auth_user['user_id'],
            $enrollment_data['course_id'],
            $enrollment_data['course_title'],
            $enrollment_data['amount_paid'],
            $enrollment_data['payment_method'] ?? 'free',
            $enrollment_data['payment_id'] ?? null,
            json_encode($enrollment_data['payment_details'] ?? null),
            json_encode($enrollment_data['student_info']),
            $payment_status
        ]);
        
        $enrollment_id = $db->lastInsertId();
        
        // Add special message for QR payments
        $message = 'Course enrollment successful';
        if (isset($enrollment_data['payment_details']['qr_payment']) && $enrollment_data['payment_details']['qr_payment']) {
            $message = 'QR payment received. Enrollment pending verification.';
        }
        
        echo json_encode([
            'success' => true,
            'enrollment_id' => $enrollment_id,
            'message' => $message,
            'payment_status' => $payment_status,
            'requires_verification' => isset($enrollment_data['payment_details']['qr_payment']) && $enrollment_data['payment_details']['qr_payment']
        ]);
        
    } catch (Exception $e) {
        error_log('Course enrollment error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to enroll in course']);
    }
}

function getMyForms() {
    global $application;
    
    $auth_user = authenticate();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $applications = $application->getByUserId($auth_user['user_id'], $limit, $offset);
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'page' => $page,
        'limit' => $limit,
        'user_id' => $auth_user['user_id'] // Add for debugging
    ]);
}

function updateApplicationStatus() {
    global $application;
    
    $auth_user = authenticate();
    
    // Check if user is admin
    if ($auth_user['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin role required.']);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: id and status']);
        return;
    }
    
    $result = $application->updateStatus($data['id'], $data['status']);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Application status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update application status']);
    }
}
?>