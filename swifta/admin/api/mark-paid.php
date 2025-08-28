<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../classes/LoanManager.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $loan_id = $input['loan_id'] ?? 0;
    
    if (!$loan_id) {
        throw new Exception('Loan ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get loan details
    $query = "SELECT * FROM loans WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        throw new Exception('Active loan not found');
    }
    
    // Mark loan as completed
    $query = "UPDATE loans SET status = 'completed' WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$loan_id])) {
        // Add payment record
        $query = "INSERT INTO payments (loan_id, amount, payment_date, payment_method, notes) 
                  VALUES (?, ?, CURDATE(), 'Manual', 'Marked as paid by admin')";
        $stmt = $db->prepare($query);
        $stmt->execute([$loan_id, $loan['total_payable']]);
        
        echo json_encode(['success' => true, 'message' => 'Loan marked as paid successfully']);
    } else {
        throw new Exception('Failed to update loan status');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>