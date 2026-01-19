<?php
class Banker {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->createTables();
    }

    private function createTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS lenders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(50) UNIQUE NOT NULL,
                email_domain VARCHAR(100),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS bankers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                lender_id INT NOT NULL,
                banker_name VARCHAR(255) NOT NULL,
                mobile_number VARCHAR(20) NOT NULL,
                official_email VARCHAR(255) NOT NULL,
                profile ENUM('sales-executive', 'sales-manager', 'cluster-sales-manager', 'area-sales-manager', 'zonal-sales-manager', 'national-sales-manager') NOT NULL,
                reporting_to INT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (lender_id) REFERENCES lenders(id),
                FOREIGN KEY (reporting_to) REFERENCES bankers(id)
            )",
            "CREATE TABLE IF NOT EXISTS territories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                banker_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                distance DECIMAL(10,2) NOT NULL,
                latitude DECIMAL(10,8) NOT NULL,
                longitude DECIMAL(11,8) NOT NULL,
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (banker_id) REFERENCES bankers(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS case_types (
                id INT PRIMARY KEY AUTO_INCREMENT,
                territory_id INT NOT NULL,
                type ENUM('sale-purchase', 'normal-refinance', 'balance-transfer') NOT NULL,
                remarks TEXT,
                loan_capping DECIMAL(15,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (territory_id) REFERENCES territories(id) ON DELETE CASCADE
            )"
        ];

        foreach ($tables as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Table creation error: " . $e->getMessage());
            }
        }

        $this->insertDefaultLenders();
    }

    private function insertDefaultLenders() {
        $lenders = [
            ['HDFC Bank', 'hdfc', '@hdfcbank.com'],
            ['ICICI Bank', 'icici', '@icicibank.com'],
            ['State Bank of India', 'sbi', '@sbi.co.in'],
            ['Axis Bank', 'axis', '@axisbank.com'],
            ['Kotak Mahindra Bank', 'kotak', '@kotak.com']
        ];

        $stmt = $this->conn->prepare("INSERT IGNORE INTO lenders (name, code, email_domain) VALUES (?, ?, ?)");
        foreach ($lenders as $lender) {
            $stmt->execute($lender);
        }
    }

    public function getAll() {
        $query = "SELECT b.*, l.name as lender_name, r.banker_name as reporting_to_name 
                  FROM bankers b 
                  LEFT JOIN lenders l ON b.lender_id = l.id 
                  LEFT JOIN bankers r ON b.reporting_to = r.id 
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT b.*, l.name as lender_name 
                  FROM bankers b 
                  LEFT JOIN lenders l ON b.lender_id = l.id 
                  WHERE b.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO bankers (lender_id, banker_name, mobile_number, official_email, profile, reporting_to, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            $data['lender_id'],
            $data['banker_name'],
            $data['mobile_number'],
            $data['official_email'],
            $data['profile'],
            $data['reporting_to'] ?? null,
            $data['status'] ?? 'active'
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE bankers SET lender_id = ?, banker_name = ?, mobile_number = ?, 
                  official_email = ?, profile = ?, reporting_to = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $data['lender_id'],
            $data['banker_name'],
            $data['mobile_number'],
            $data['official_email'],
            $data['profile'],
            $data['reporting_to'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM bankers WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getLenders() {
        $query = "SELECT * FROM lenders WHERE is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createLender($data) {
        $query = "INSERT INTO lenders (name, code, email_domain, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            $data['name'],
            $data['code'],
            $data['email_domain'] ?? null,
            $data['is_active'] ?? true
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function addTerritory($banker_id, $data) {
        $query = "INSERT INTO territories (banker_id, name, distance, latitude, longitude, address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            $banker_id,
            $data['name'],
            $data['distance'],
            $data['latitude'],
            $data['longitude'],
            $data['address'] ?? null
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function addCaseType($territory_id, $data) {
        $query = "INSERT INTO case_types (territory_id, type, remarks, loan_capping) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            $territory_id,
            $data['type'],
            $data['remarks'] ?? null,
            $data['loan_capping'] ?? null
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>