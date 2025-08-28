<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../classes/CustomerManager.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $customerManager = new CustomerManager();
    
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'employment_status' => $_POST['employment_status'] ?? '',
        'monthly_income' => floatval($_POST['monthly_income'] ?? 0)
    ];
    
    // Validate required fields
    if (empty($data['full_name']) || empty($data['phone_number']) || empty($data['address'])) {
        throw new Exception('Please fill in all required fields');
    }
    
    if ($customerManager->createCustomer($data)) {
        echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
    } else {
        throw new Exception('Failed to add customer');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>