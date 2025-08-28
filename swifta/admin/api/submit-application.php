<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/CustomerManager.php';
require_once '../classes/LoanManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required_fields = ['fullName', 'phoneNumber', 'address', 'loanAmount', 'loanTerm', 'purpose', 'employment', 'monthlyIncome'];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $customerManager = new CustomerManager();
    $loanManager = new LoanManager();
    
    // Check if customer already exists
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM customers WHERE phone_number = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['phoneNumber']]);
    $existing_customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_customer) {
        $customer_id = $existing_customer['id'];
        
        // Update customer information
        $customer_data = [
            'full_name' => $input['fullName'],
            'phone_number' => $input['phoneNumber'],
            'email' => $input['email'] ?? '',
            'address' => $input['address'],
            'employment_status' => $input['employment'],
            'monthly_income' => floatval($input['monthlyIncome'])
        ];
        
        $customerManager->updateCustomer($customer_id, $customer_data);
    } else {
        // Create new customer
        $customer_data = [
            'full_name' => $input['fullName'],
            'phone_number' => $input['phoneNumber'],
            'email' => $input['email'] ?? '',
            'address' => $input['address'],
            'employment_status' => $input['employment'],
            'monthly_income' => floatval($input['monthlyIncome'])
        ];
        
        if (!$customerManager->createCustomer($customer_data)) {
            throw new Exception('Failed to create customer record');
        }
        
        // Get the new customer ID
        $stmt = $db->prepare($query);
        $stmt->execute([$input['phoneNumber']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $customer_id = $customer['id'];
    }
    
    // Calculate loan details
    $loan_amount = floatval($input['loanAmount']);
    $loan_term = intval($input['loanTerm']);
    
    // Interest rates
    $interest_rates = [7 => 23, 14 => 30, 21 => 35, 28 => 45];
    $interest_rate = $interest_rates[$loan_term] ?? 45;
    
    $total_payable = $loan_amount * (1 + ($interest_rate / 100));
    
    // Create loan application
    $loan_data = [
        'customer_id' => $customer_id,
        'loan_amount' => $loan_amount,
        'interest_rate' => $interest_rate,
        'loan_term_days' => $loan_term,
        'total_payable' => $total_payable,
        'purpose' => $input['purpose']
    ];
    
    if ($loanManager->createLoan($loan_data)) {
        // Send confirmation email/SMS (implement as needed)
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan application submitted successfully',
            'loan_id' => $db->lastInsertId(),
            'customer_id' => $customer_id
        ]);
    } else {
        throw new Exception('Failed to create loan application');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>