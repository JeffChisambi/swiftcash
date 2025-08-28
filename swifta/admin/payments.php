<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get all payments with loan and customer information
$query = "SELECT p.*, l.loan_amount, l.total_payable, l.status as loan_status,
          c.full_name, c.phone_number,
          CONCAT('SC', LPAD(l.id, 3, '0')) as loan_id_formatted
          FROM payments p
          JOIN loans l ON p.loan_id = l.id
          JOIN customers c ON l.customer_id = c.id
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$stats_query = "SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    COUNT(CASE WHEN DATE(payment_date) = CURDATE() THEN 1 END) as today_payments,
    SUM(CASE WHEN DATE(payment_date) = CURDATE() THEN amount ELSE 0 END) as today_amount
    FROM payments";

$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - SwiftCash Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-active { background: linear-gradient(135deg, #1A4E8A, #F5A623); }
        .card-hover:hover { transform: translateY(-2px); transition: all 0.3s ease; }
        
        /* Dark mode styles */
        .dark { background-color: #1a202c; color: #e2e8f0; }
        .dark .bg-white { background-color: #2d3748; }
        .dark .text-gray-900 { color: #e2e8f0; }
        .dark .text-gray-800 { color: #cbd5e0; }
        .dark .text-gray-700 { color: #a0aec0; }
        .dark .text-gray-600 { color: #718096; }
        .dark .border-gray-100 { border-color: #4a5568; }
        .dark .bg-gray-50 { background-color: #2d3748; }
        .dark .bg-gray-100 { background-color: #4a5568; }
    </style>
</head>
<body class="bg-gray-50 transition-colors duration-300" id="body">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg dark:bg-gray-800">
            <div class="p-6 border-b dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 dark:text-white">SwiftCash Admin</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Loan Management System</p>
                    </div>
                    <button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
            
            <nav class="mt-6">
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="customers.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-users mr-3"></i>
                    Customers
                </a>
                <a href="loans.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    Loans
                </a>
                <a href="payments.php" class="flex items-center px-6 py-3 text-white sidebar-active">
                    <i class="fas fa-credit-card mr-3"></i>
                    Payments
                </a>
                <a href="reminders.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-bell mr-3"></i>
                    Reminders
                </a>
                <a href="reports.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Reports
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6 border-t dark:border-gray-700">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold"><?= substr($_SESSION['admin_name'], 0, 1) ?></span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-white"><?= $_SESSION['admin_name'] ?></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400"><?= ucfirst($_SESSION['admin_role']) ?></p>
                    </div>
                </div>
                <a href="logout.php" class="mt-3 text-sm text-red-600 hover:text-red-800">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-6 py-4 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Payment Management</h2>
                    <div class="flex items-center space-x-4">
                        <button onclick="openAddPaymentModal()" class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>Record Payment
                        </button>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Payments Content -->
            <main class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center dark:bg-green-900">
                                <i class="fas fa-money-bill text-green-600 text-xl dark:text-green-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Payments</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_payments'] ?></p>
                                <p class="text-sm text-green-600">MWK <?= number_format($stats['total_amount'], 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center dark:bg-blue-900">
                                <i class="fas fa-calendar-day text-blue-600 text-xl dark:text-blue-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Payments</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['today_payments'] ?></p>
                                <p class="text-sm text-blue-600">MWK <?= number_format($stats['today_amount'], 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center dark:bg-orange-900">
                                <i class="fas fa-chart-line text-orange-600 text-xl dark:text-orange-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Payment</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    MWK <?= $stats['total_payments'] > 0 ? number_format($stats['total_amount'] / $stats['total_payments'], 0) : '0' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center dark:bg-purple-900">
                                <i class="fas fa-mobile-alt text-purple-600 text-xl dark:text-purple-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mobile Money</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($payments, function($p) { return strpos(strtolower($p['payment_method'] ?? ''), 'mobile') !== false; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 dark:bg-gray-800">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" id="searchPayments" placeholder="Search by customer name or loan ID..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <select id="filterMethod" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Methods</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                        <input type="date" id="filterDate" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Payment ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Loan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" id="paymentsTableBody">
                                <?php foreach ($payments as $payment): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">P<?= str_pad($payment['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-bold"><?= substr($payment['full_name'], 0, 1) ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= $payment['full_name'] ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= $payment['phone_number'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?= $payment['loan_id_formatted'] ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php
                                            $statusColors = [
                                                'active' => 'text-blue-600',
                                                'completed' => 'text-green-600',
                                                'overdue' => 'text-red-600'
                                            ];
                                            $statusClass = $statusColors[$payment['loan_status']] ?? 'text-gray-600';
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= ucfirst($payment['loan_status']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">MWK <?= number_format($payment['amount'], 2) ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            of MWK <?= number_format($payment['total_payable'], 2) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= $payment['payment_method'] ?: 'Not specified' ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= date('M j, Y', strtotime($payment['payment_date'])) ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?= date('g:i A', strtotime($payment['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= $payment['reference_number'] ?: 'N/A' ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 dark:bg-gray-800">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Record Payment</h3>
                    <button onclick="closeAddPaymentModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="addPaymentForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loan ID</label>
                            <input type="text" name="loan_id" required placeholder="SC001" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount (MWK)</label>
                            <input type="number" name="amount" required step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                            <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select method</option>
                                <option value="Airtel Money">Airtel Money</option>
                                <option value="TNM Mpamba">TNM Mpamba</option>
                                <option value="FDH Mobile">FDH Mobile</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference Number</label>
                            <input type="text" name="reference_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Date</label>
                            <input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" onclick="closeAddPaymentModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-lg hover:shadow-lg">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Dark mode functionality
        function toggleDarkMode() {
            const body = document.getElementById('body');
            const isDark = body.classList.contains('dark');
            
            if (isDark) {
                body.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            } else {
                body.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            }
        }

        // Initialize dark mode
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.getElementById('body').classList.add('dark');
            }
        });

        // Modal functions
        function openAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.remove('hidden');
        }

        function closeAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.add('hidden');
        }

        // Search functionality
        document.getElementById('searchPayments').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#paymentsTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Filter functionality
        document.getElementById('filterMethod').addEventListener('change', function(e) {
            const filterValue = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#paymentsTableBody tr');
            
            rows.forEach(row => {
                if (filterValue === '') {
                    row.style.display = '';
                } else {
                    const methodCell = row.querySelector('td:nth-child(5)');
                    const method = methodCell.textContent.toLowerCase().trim();
                    if (method.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });

        // Add payment form submission
        document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment recorded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while recording the payment.');
            });
        });
    </script>
</body>
</html>