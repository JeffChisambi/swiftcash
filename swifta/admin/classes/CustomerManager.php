<?php
require_once 'config/database.php';

class CustomerManager {
    private $conn;
    private $table_name = "customers";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllCustomers() {
        $query = "SELECT c.*, 
                  COUNT(l.id) as total_loans,
                  SUM(CASE WHEN l.status = 'active' THEN l.loan_amount ELSE 0 END) as active_loan_amount
                  FROM " . $this->table_name . " c 
                  LEFT JOIN loans l ON c.id = l.customer_id 
                  GROUP BY c.id 
                  ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCustomerLoans($customer_id) {
        $query = "SELECT l.*, p.amount as paid_amount 
                  FROM loans l 
                  LEFT JOIN (SELECT loan_id, SUM(amount) as amount FROM payments GROUP BY loan_id) p ON l.id = p.loan_id
                  WHERE l.customer_id = ? 
                  ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCustomer($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (full_name, phone_number, email, address, employment_status, monthly_income) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['email'],
            $data['address'],
            $data['employment_status'],
            $data['monthly_income']
        ]);
    }

    public function updateCustomer($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = ?, phone_number = ?, email = ?, address = ?, employment_status = ?, monthly_income = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['email'],
            $data['address'],
            $data['employment_status'],
            $data['monthly_income'],
            $id
        ]);
    }
}
?>