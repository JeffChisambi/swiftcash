<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Extract loan ID from format like "SC001"
    $loan_id_input = $_POST['loan_id'] ?? '';
    $loan_id = (int) preg_replace('/[^0-9]/', '', $loan_id_input);
    
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = $_POST['reference_number'] ?? '';
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (!$loan_id || $amount <= 0 || empty($payment_method)) {
        throw new Exception('Please fill in all required fields');
    }
    
    // Check if loan exists
    $query = "SELECT * FROM loans WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        throw new Exception('Loan not found');
    }
    
    // Insert payment record
    $query = "INSERT INTO payments (loan_id, amount, payment_date, payment_method, reference_number, notes) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$loan_id, $amount, $payment_date, $payment_method, $reference_number, $notes])) {
        // Check if loan is fully paid
        $query = "SELECT SUM(amount) as total_paid FROM payments WHERE loan_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$loan_id]);
        $payment_total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment_total['total_paid'] >= $loan['total_payable']) {
            // Mark loan as completed
            $query = "UPDATE loans SET status = 'completed' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$loan_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
    } else {
        throw new Exception('Failed to record payment');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>