<?php
class Database {
    private $host = 'eok0ss8kgwwsc8s8sw8g0840';
    private $db_name = 'Fino';
    private $username = 'mysql';
    private $password = 'Root@6378110608#';
    private $port = '3306';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";
            $options = [
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Helper function for backward compatibility
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>
