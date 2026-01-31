<?php
require_once __DIR__ . '/../middleware/cors_secure.php';
SecureCorsMiddleware::handle();

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
        image VARCHAR(500),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($createJobsTable);

    // Add image column if it doesn't exist (for existing tables)
    try {
        @$db->exec("ALTER TABLE career_jobs ADD COLUMN image VARCHAR(500) NULL");
    } catch (Exception $e) {
        // Silently ignore if column exists
    }

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
                
                // Add cv_path if missing
                foreach ($applications as &$app) {
                    if (!isset($app['cv_path']) && isset($app['cv_filename'])) {
                        $app['cv_path'] = 'uploads/cvs/' . $app['cv_filename'];
                    }
                }
                
                echo json_encode(['success' => true, 'applications' => $applications]);
            }
            break;

        case 'POST':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs') {
                // Check if this is an update (has job ID in URL)
                $isUpdate = isset($pathParts[3]) && is_numeric($pathParts[3]);
                $jobId = $isUpdate ? $pathParts[3] : null;
                
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
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'https://api.finonest.com/uploads/job-images/' . $fileName;
                    }
                }
                
                if ($isUpdate) {
                    // Update existing job
                    if ($imagePath) {
                        $stmt = $db->prepare("UPDATE career_jobs SET title=?, department=?, location=?, type=?, salary=?, description=?, requirements=?, image=? WHERE id=?");
                        $stmt->execute([
                            $title,
                            $department,
                            $_POST['location'] ?? '',
                            $_POST['type'] ?? 'Full-time',
                            $_POST['salary'] ?? '',
                            $description,
                            $_POST['requirements'] ?? '',
                            $imagePath,
                            $jobId
                        ]);
                    } else {
                        $stmt = $db->prepare("UPDATE career_jobs SET title=?, department=?, location=?, type=?, salary=?, description=?, requirements=? WHERE id=?");
                        $stmt->execute([
                            $title,
                            $department,
                            $_POST['location'] ?? '',
                            $_POST['type'] ?? 'Full-time',
                            $_POST['salary'] ?? '',
                            $description,
                            $_POST['requirements'] ?? '',
                            $jobId
                        ]);
                    }
                    echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
                } else {
                    // Create new job
                    $stmt = $db->prepare("INSERT INTO career_jobs (title, department, location, type, salary, description, requirements, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $title,
                        $department,
                        $_POST['location'] ?? '',
                        $_POST['type'] ?? 'Full-time',
                        $_POST['salary'] ?? '',
                        $description,
                        $_POST['requirements'] ?? '',
                        $imagePath
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Job created successfully', 'job_id' => $db->lastInsertId()]);
                }
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'apply') {
                // Submit application with CV upload
                $job_id = $_POST['job_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if (!$job_id || !$name || !$email) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    exit();
                }
                
                $cvFilename = null;
                $cvPath = null;
                if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/cvs/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Validate file type
                    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $fileType = $_FILES['cv_file']['type'];
                    $fileExtension = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['pdf', 'doc', 'docx'];
                    
                    if (!in_array($fileType, $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid file type. Only PDF, DOC, and DOCX files are allowed.']);
                        exit();
                    }
                    
                    // Validate file size (5MB max)
                    if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
                        http_response_code(400);
                        echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
                        exit();
                    }
                    
                    $cvFilename = $_FILES['cv_file']['name'];
                    $fileName = time() . '_' . basename($cvFilename);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $targetPath)) {
                        $cvPath = 'uploads/cvs/' . $fileName;
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to upload CV file.']);
                        exit();
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO career_applications (job_id, name, email, phone, experience, cover_letter, cv_filename, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $job_id,
                    $name,
                    $email,
                    $_POST['phone'] ?? '',
                    $_POST['experience'] ?? '',
                    $_POST['cover_letter'] ?? '',
                    $cvFilename,
                    $cvPath
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
            }
            break;

        case 'PUT':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs' && isset($pathParts[3])) {
                // Update job - Parse input for PUT request
                $jobId = $pathParts[3];
                
                // For PUT requests, we need to parse the input differently
                $input = [];
                if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                    // Handle multipart form data for PUT
                    $input = $_POST;
                } else {
                    // Handle JSON input
                    $rawInput = file_get_contents('php://input');
                    parse_str($rawInput, $input);
                }
                
                $title = $input['title'] ?? $_POST['title'] ?? '';
                $department = $input['department'] ?? $_POST['department'] ?? '';
                $description = $input['description'] ?? $_POST['description'] ?? '';
                
                if (!$title || !$department || !$description) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields', 'received' => $input]);
                    exit();
                }
                
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/job-images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'https://api.finonest.com/uploads/job-images/' . $fileName;
                    }
                }
                
                if ($imagePath) {
                    $stmt = $db->prepare("UPDATE career_jobs SET title=?, department=?, location=?, type=?, salary=?, description=?, requirements=?, image=? WHERE id=?");
                    $stmt->execute([
                        $title,
                        $department,
                        $input['location'] ?? $_POST['location'] ?? '',
                        $input['type'] ?? $_POST['type'] ?? 'Full-time',
                        $input['salary'] ?? $_POST['salary'] ?? '',
                        $description,
                        $input['requirements'] ?? $_POST['requirements'] ?? '',
                        $imagePath,
                        $jobId
                    ]);
                } else {
                    $stmt = $db->prepare("UPDATE career_jobs SET title=?, department=?, location=?, type=?, salary=?, description=?, requirements=? WHERE id=?");
                    $stmt->execute([
                        $title,
                        $department,
                        $input['location'] ?? $_POST['location'] ?? '',
                        $input['type'] ?? $_POST['type'] ?? 'Full-time',
                        $input['salary'] ?? $_POST['salary'] ?? '',
                        $description,
                        $input['requirements'] ?? $_POST['requirements'] ?? '',
                        $jobId
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications' && isset($pathParts[3])) {
                // Update application status
                $applicationId = $pathParts[3];
                $input = json_decode(file_get_contents('php://input'), true);
                $status = $input['status'] ?? '';
                
                if (!$status) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Status is required']);
                    exit();
                }
                
                $stmt = $db->prepare("UPDATE career_applications SET status=? WHERE id=?");
                $stmt->execute([$status, $applicationId]);
                
                echo json_encode(['success' => true, 'message' => 'Application status updated successfully']);
            }
            break;

        case 'DELETE':
            if (isset($pathParts[2]) && $pathParts[2] === 'jobs' && isset($pathParts[3])) {
                // Delete job
                $jobId = $pathParts[3];
                $stmt = $db->prepare("DELETE FROM career_jobs WHERE id=?");
                $stmt->execute([$jobId]);
                echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
            } elseif (isset($pathParts[2]) && $pathParts[2] === 'applications' && isset($pathParts[3])) {
                // Delete application
                $applicationId = $pathParts[3];
                $stmt = $db->prepare("DELETE FROM career_applications WHERE id=?");
                $stmt->execute([$applicationId]);
                echo json_encode(['success' => true, 'message' => 'Application deleted successfully']);
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