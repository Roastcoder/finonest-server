<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/jwt.php';

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

// Create tables if not exist
try {
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
        FOREIGN KEY (job_id) REFERENCES career_jobs(id) ON DELETE CASCADE
    )";
    $db->exec($createApplicationsTable);
} catch (Exception $e) {
    // Tables might already exist
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

switch($method) {
    case 'GET':
        if (isset($request[0]) && $request[0] === 'jobs') {
            if (isset($request[1])) {
                getJob($request[1]);
            } else {
                getJobs();
            }
        } elseif (isset($request[0]) && $request[0] === 'applications') {
            requireAdmin();
            getApplications();
        }
        break;
    case 'POST':
        if (isset($request[0]) && $request[0] === 'jobs') {
            requireAdmin();
            createJob();
        } elseif (isset($request[0]) && $request[0] === 'apply') {
            submitApplication();
        }
        break;
    case 'PUT':
        if (isset($request[0]) && $request[0] === 'jobs' && isset($request[1])) {
            requireAdmin();
            updateJob($request[1]);
        } elseif (isset($request[0]) && $request[0] === 'applications' && isset($request[1])) {
            requireAdmin();
            updateApplication($request[1]);
        }
        break;
    case 'DELETE':
        if (isset($request[0]) && $request[0] === 'jobs' && isset($request[1])) {
            requireAdmin();
            deleteJob($request[1]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getJobs() {
    global $db;
    
    try {
        $query = "SELECT *, DATE(created_at) as posted_date FROM career_jobs WHERE status = 'active' ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'jobs' => $jobs
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch jobs']);
    }
}

function getJob($jobId) {
    global $db;
    
    try {
        $query = "SELECT *, DATE(created_at) as posted_date FROM career_jobs WHERE id = ? AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($job) {
            echo json_encode([
                'success' => true,
                'job' => $job
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch job']);
    }
}

function createJob() {
    global $db;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required_fields = ['title', 'department', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $query = "INSERT INTO career_jobs (title, department, location, type, salary, description, requirements) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['department'],
            $data['location'] ?? '',
            $data['type'] ?? 'Full-time',
            $data['salary'] ?? '',
            $data['description'],
            $data['requirements'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Job posted successfully',
            'job_id' => $db->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create job']);
    }
}

function updateJob($jobId) {
    global $db;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        $query = "UPDATE career_jobs SET title = ?, department = ?, location = ?, type = ?, salary = ?, description = ?, requirements = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['department'],
            $data['location'] ?? '',
            $data['type'] ?? 'Full-time',
            $data['salary'] ?? '',
            $data['description'],
            $data['requirements'] ?? '',
            $jobId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Job updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update job']);
    }
}

function deleteJob($jobId) {
    global $db;
    
    try {
        $query = "DELETE FROM career_jobs WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$jobId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Job deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete job']);
    }
}

function submitApplication() {
    global $db;
    
    $job_id = $_POST['job_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $cover_letter = $_POST['cover_letter'] ?? '';
    
    if (!$job_id || !$name || !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Handle file upload
    $cv_filename = '';
    $cv_path = '';
    
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/cvs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
        $cv_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $cv_path = $upload_dir . $cv_filename;
        
        if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $cv_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload CV']);
            return;
        }
    }
    
    try {
        $query = "INSERT INTO career_applications (job_id, name, email, phone, experience, cover_letter, cv_filename, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $job_id,
            $name,
            $email,
            $phone,
            $experience,
            $cover_letter,
            $cv_filename,
            $cv_path
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit application']);
    }
}

function getApplications() {
    global $db;
    
    try {
        $query = "SELECT a.*, j.title as job_title, DATE(a.created_at) as applied_date 
                  FROM career_applications a 
                  JOIN career_jobs j ON a.job_id = j.id 
                  ORDER BY a.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'applications' => $applications
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch applications']);
    }
}

function updateApplication($applicationId) {
    global $db;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        $query = "UPDATE career_applications SET status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data['status'], $applicationId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Application status updated'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update application']);
    }
}
?>