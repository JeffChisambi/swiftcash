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
    
    $loanManager = new LoanManager();
    
    // Update loan status to active and set disbursement date
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE loans SET 
              status = 'active', 
              disbursement_date = CURDATE(),
              due_date = DATE_ADD(CURDATE(), INTERVAL loan_term_days DAY)
              WHERE id = ? AND status = 'pending'";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$loan_id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Loan approved successfully']);
        } else {
            throw new Exception('Loan not found or already processed');
        }
    } else {
        throw new Exception('Failed to approve loan');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>