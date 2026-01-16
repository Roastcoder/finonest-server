<?php
class Application {
    private $conn;
    private $table_name = "loan_applications";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $form_data) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, type, amount, purpose, income, employment) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        $type = $this->mapLoanType($form_data['loan_type'] ?? 'PERSONAL');
        $amount = $form_data['amount'] ?? 0;
        $purpose = $form_data['notes'] ?? '';
        $income = $form_data['monthly_income'] ?? 0;
        $employment = $form_data['employment_type'] ?? '';
        
        if($stmt->execute([$user_id, $type, $amount, $purpose, $income, $employment])) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    private function mapLoanType($loan_type) {
        $mapping = [
            'Personal Loan' => 'PERSONAL',
            'Home Loan' => 'HOME',
            'Business Loan' => 'BUSINESS',
            'Car Loan' => 'VEHICLE',
            'Used Car Loan' => 'VEHICLE',
            'Loan Against Property' => 'LAP'
        ];
        return $mapping[$loan_type] ?? 'PERSONAL';
    }

    public function getByUserId($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($limit = 10, $offset = 0, $status = null) {
        $query = "SELECT a.*, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " a 
                  JOIN users u ON a.user_id = u.id";
        
        if ($status) {
            $query .= " WHERE a.status = '$status'";
        }
        
        $query .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>