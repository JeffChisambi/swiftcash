<?php
require_once 'config/database.php';

class LoanManager {
    private $conn;
    private $table_name = "loans";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getTotalLoansIssued() {
        $query = "SELECT COUNT(*) as total, SUM(loan_amount) as total_amount 
                  FROM " . $this->table_name . " 
                  WHERE status != 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentlyRepaidLoans($limit = 10) {
        // Ensure limit is a safe positive integer
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 10;
        }

        $query = "SELECT l.*, c.full_name, c.phone_number 
                  FROM " . $this->table_name . " l 
                  JOIN customers c ON l.customer_id = c.id 
                  WHERE l.status = 'completed' 
                  ORDER BY l.updated_at DESC 
                  LIMIT $limit";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcomingRepayments($days = 7) {
        // Ensure days is a safe positive integer
        $days = (int)$days;
        if ($days <= 0) {
            $days = 7;
        }

        $query = "SELECT l.*, c.full_name, c.phone_number, c.email,
                         DATEDIFF(l.due_date, CURDATE()) as days_remaining
                  FROM " . $this->table_name . " l 
                  JOIN customers c ON l.customer_id = c.id 
                  WHERE l.status = 'active' 
                  AND l.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY)
                  ORDER BY l.due_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueLoans() {
        $query = "SELECT l.*, c.full_name, c.phone_number, c.email,
                         DATEDIFF(CURDATE(), l.due_date) as days_overdue
                  FROM " . $this->table_name . " l 
                  JOIN customers c ON l.customer_id = c.id 
                  WHERE l.status = 'active' 
                  AND l.due_date < CURDATE()
                  ORDER BY l.due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createLoan($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (customer_id, loan_amount, interest_rate, loan_term_days, total_payable, purpose, status, disbursement_date, due_date) 
                  VALUES (?, ?, ?, ?, ?, ?, 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['customer_id'],
            $data['loan_amount'],
            $data['interest_rate'],
            $data['loan_term_days'],
            $data['total_payable'],
            $data['purpose'],
            $data['loan_term_days']
        ]);
    }

    public function updateLoanStatus($loan_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $loan_id]);
    }

    public function calculateLateFee($loan_amount, $days_overdue) {
        if ($days_overdue >= 7) {
            return $loan_amount * 0.15; // 15% late fee
        }
        return 0;
    }
}
?>
