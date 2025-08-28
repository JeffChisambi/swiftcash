<?php
require_once 'includes/auth.php';
require_once 'classes/LoanManager.php';
require_once 'classes/CustomerManager.php';

requireLogin();

$loanManager = new LoanManager();
$customerManager = new CustomerManager();
$customers = $customerManager->getAllCustomers();

$success = '';
$error = '';

if ($_POST) {
    try {
        // Calculate interest rate based on term
        $term = (int)$_POST['loan_term_days'];
        $interest_rates = [7 => 23, 14 => 30, 21 => 35, 28 => 45];
        $interest_rate = $interest_rates[$term] ?? 45;
        
        $loan_amount = (float)$_POST['loan_amount'];
        $total_payable = $loan_amount * (1 + ($interest_rate / 100));
        
        $loan_data = [
            'customer_id' => $_POST['customer_id'],
            'loan_amount' => $loan_amount,
            'interest_rate' => $interest_rate,
            'loan_term_days' => $term,
            'total_payable' => $total_payable,
            'purpose' => $_POST['purpose']
        ];
        
        if ($loanManager->createLoan($loan_data)) {
            $success = 'Loan created successfully!';
        } else {
            $error = 'Failed to create loan. Please try again.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Loan - SwiftCash Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Register New Loan</h1>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto p-6">
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Loan Application Details</h2>
                </div>

                <form method="POST" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Customer Selection -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                            <select name="customer_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Choose a customer...</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>">
                                        <?= $customer['full_name'] ?> - <?= $customer['phone_number'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Loan Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loan Amount (MWK)</label>
                            <input type="number" name="loan_amount" required min="10000" max="1000000" step="1000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   id="loanAmount" onchange="calculateTotal()">
                        </div>

                        <!-- Loan Term -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loan Term</label>
                            <select name="loan_term_days" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    id="loanTerm" onchange="calculateTotal()">
                                <option value="">Select term...</option>
                                <option value="7">1 Week (23% interest)</option>
                                <option value="14">2 Weeks (30% interest)</option>
                                <option value="21">3 Weeks (35% interest)</option>
                                <option value="28">4 Weeks (45% interest)</option>
                            </select>
                        </div>

                        <!-- Purpose -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Purpose of Loan</label>
                            <select name="purpose" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Select purpose...</option>
                                <option value="business">Business expenses</option>
                                <option value="education">Education</option>
                                <option value="medical">Medical expenses</option>
                                <option value="home">Home improvement</option>
                                <option value="emergency">Emergency</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Loan Summary -->
                    <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-orange-50 rounded-lg border">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Summary</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Principal Amount</p>
                                <p class="text-lg font-bold text-gray-900" id="principalAmount">MWK 0</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Interest Rate</p>
                                <p class="text-lg font-bold text-blue-600" id="interestRate">0%</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Interest Amount</p>
                                <p class="text-lg font-bold text-orange-600" id="interestAmount">MWK 0</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Payable</p>
                                <p class="text-xl font-bold text-green-600" id="totalPayable">MWK 0</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end space-x-4">
                        <a href="index.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>Create Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateTotal() {
            const amount = parseFloat(document.getElementById('loanAmount').value) || 0;
            const term = parseInt(document.getElementById('loanTerm').value) || 0;
            
            const rates = {7: 23, 14: 30, 21: 35, 28: 45};
            const rate = rates[term] || 0;
            
            const interest = amount * (rate / 100);
            const total = amount + interest;
            
            document.getElementById('principalAmount').textContent = 'MWK ' + amount.toLocaleString();
            document.getElementById('interestRate').textContent = rate + '%';
            document.getElementById('interestAmount').textContent = 'MWK ' + interest.toLocaleString();
            document.getElementById('totalPayable').textContent = 'MWK ' + total.toLocaleString();
        }
    </script>
</body>
</html>