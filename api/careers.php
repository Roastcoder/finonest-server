<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://finonest.com');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

header('Access-Control-Allow-Origin: https://finonest.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    
    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS career_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') DEFAULT 'Full-time',
        salary VARCHAR(100),
        description TEXT NOT NULL,
        requirements TEXT,
        image VARCHAR(500),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS career_applications (
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    switch ($method) {
        case 'GET':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs') {
                if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                    $stmt = $db->prepare("SELECT * FROM career_jobs WHERE id = ? AND status = 'active'");
                    $stmt->execute([$pathParts[3]]);
                    $job = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($job ? ['success' => true, 'job' => $job] : ['error' => 'Job not found']);
                } else {
                    $stmt = $db->prepare("SELECT * FROM career_jobs WHERE status = 'active' ORDER BY created_at DESC");
                    $stmt->execute();
                    echo json_encode(['success' => true, 'jobs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                }
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications') {
                $stmt = $db->prepare("SELECT a.*, j.title as job_title FROM career_applications a JOIN career_jobs j ON a.job_id = j.id ORDER BY a.created_at DESC");
                $stmt->execute();
                echo json_encode(['success' => true, 'applications' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;

        case 'POST':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs') {
                $title = $_POST['title'] ?? '';
                $department = $_POST['department'] ?? '';
                $description = $_POST['description'] ?? '';
                
                if (!$title || !$department || !$description) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit();
                }
                
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/job-images/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        $imagePath = 'https://api.finonest.com/uploads/job-images/' . $fileName;
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO career_jobs (title, department, location, type, salary, description, requirements, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title, $department, $_POST['location'] ?? '', $_POST['type'] ?? 'Full-time',
                    $_POST['salary'] ?? '', $description, $_POST['requirements'] ?? '', $imagePath
                ]);
                echo json_encode(['success' => true, 'message' => 'Job created', 'job_id' => $db->lastInsertId()]);
                
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'apply') {
                $job_id = $_POST['job_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if (!$job_id || !$name || !$email) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit();
                }
                
                $cvPath = null;
                if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/cvs/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    $fileName = time() . '_' . basename($_FILES['cv_file']['name']);
                    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $uploadDir . $fileName)) {
                        $cvPath = 'uploads/cvs/' . $fileName;
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO career_applications (job_id, name, email, phone, experience, cover_letter, cv_filename, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $job_id, $name, $email, $_POST['phone'] ?? '', $_POST['experience'] ?? '',
                    $_POST['cover_letter'] ?? '', $_FILES['cv_file']['name'] ?? null, $cvPath
                ]);
                echo json_encode(['success' => true, 'message' => 'Application submitted']);
            }
            break;

        case 'PUT':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs' && isset($pathParts[3])) {
                $input = json_decode(file_get_contents('php://input'), true);
                $stmt = $db->prepare("UPDATE career_jobs SET title=?, department=?, location=?, type=?, salary=?, description=?, requirements=? WHERE id=?");
                $stmt->execute([
                    $input['title'], $input['department'], $input['location'] ?? '', $input['type'] ?? 'Full-time',
                    $input['salary'] ?? '', $input['description'], $input['requirements'] ?? '', $pathParts[3]
                ]);
                echo json_encode(['success' => true, 'message' => 'Job updated']);
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications' && isset($pathParts[3])) {
                $input = json_decode(file_get_contents('php://input'), true);
                $stmt = $db->prepare("UPDATE career_applications SET status=? WHERE id=?");
                $stmt->execute([$input['status'], $pathParts[3]]);
                echo json_encode(['success' => true, 'message' => 'Application updated']);
            }
            break;

        case 'DELETE':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs' && isset($pathParts[3])) {
                $stmt = $db->prepare("DELETE FROM career_jobs WHERE id=?");
                $stmt->execute([$pathParts[3]]);
                echo json_encode(['success' => true, 'message' => 'Job deleted']);
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications' && isset($pathParts[3])) {
                $stmt = $db->prepare("DELETE FROM career_applications WHERE id=?");
                $stmt->execute([$pathParts[3]]);
                echo json_encode(['success' => true, 'message' => 'Application deleted']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>