<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../classes/EmailReminder.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $loan_id = $input['loan_id'] ?? 0;
    
    $emailReminder = new EmailReminder();
    $count = 0;
    
    switch ($type) {
        case 'all':
            $upcomingCount = $emailReminder->sendUpcomingPaymentReminders();
            $overdueCount = $emailReminder->sendOverdueReminders();
            $count = $upcomingCount + $overdueCount;
            break;
            
        case 'individual':
            if (!$loan_id) {
                throw new Exception('Loan ID is required');
            }
            // Send individual upcoming payment reminder
            $count = $emailReminder->sendIndividualReminder($loan_id, 'upcoming');
            break;
            
        case 'overdue':
            if (!$loan_id) {
                throw new Exception('Loan ID is required');
            }
            // Send individual overdue reminder
            $count = $emailReminder->sendIndividualReminder($loan_id, 'overdue');
            break;
            
        default:
            throw new Exception('Invalid reminder type');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reminders sent successfully',
        'count' => $count
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>