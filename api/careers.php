<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Create tables if they don't exist
    $createJobsTable = "CREATE TABLE IF NOT EXISTS career_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') DEFAULT 'Full-time',
        salary VARCHAR(100),
        description TEXT NOT NULL,
        requirements TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($createJobsTable);

    $createApplicationsTable = "CREATE TABLE IF NOT EXISTS career_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        experience VARCHAR(100),
        cover_letter TEXT,
        cv_filename VARCHAR(255),
        cv_path VARCHAR(500),
        status ENUM('pending', 'reviewed', 'shortlisted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_job_id (job_id)
    )";
    $db->exec($createApplicationsTable);

    switch ($method) {
        case 'GET':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs') {
                if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                    // Get single job
                    $jobId = $pathParts[3];
                    $stmt = $db->prepare("SELECT *, DATE(created_at) as posted_date FROM career_jobs WHERE id = ? AND status = 'active'");
                    $stmt->execute([$jobId]);
                    $job = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($job) {
                        echo json_encode(['success' => true, 'job' => $job]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Job not found']);
                    }
                } else {
                    // Get all jobs
                    $stmt = $db->prepare("SELECT *, DATE(created_at) as posted_date FROM career_jobs WHERE status = 'active' ORDER BY created_at DESC");
                    $stmt->execute();
                    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'jobs' => $jobs]);
                }
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications') {
                // Get applications (admin only)
                $stmt = $db->prepare("SELECT a.*, j.title as job_title, DATE(a.created_at) as applied_date 
                                     FROM career_applications a 
                                     JOIN career_jobs j ON a.job_id = j.id 
                                     ORDER BY a.created_at DESC");
                $stmt->execute();
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'applications' => $applications]);
            }
            break;

        case 'POST':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs') {
                // Create job
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input['title'] || !$input['department'] || !$input['description']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit();
                }
                
                $stmt = $db->prepare("INSERT INTO career_jobs (title, department, location, type, salary, description, requirements) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['title'],
                    $input['department'],
                    $input['location'] ?? '',
                    $input['type'] ?? 'Full-time',
                    $input['salary'] ?? '',
                    $input['description'],
                    $input['requirements'] ?? ''
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Job created successfully', 'job_id' => $db->lastInsertId()]);
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'apply') {
                // Submit application
                $job_id = $_POST['job_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if (!$job_id || !$name || !$email) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit();
                }
                
                $stmt = $db->prepare("INSERT INTO career_applications (job_id, name, email, phone, experience, cover_letter) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $job_id,
                    $name,
                    $email,
                    $_POST['phone'] ?? '',
                    $_POST['experience'] ?? '',
                    $_POST['cover_letter'] ?? ''
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>