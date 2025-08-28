<?php
require_once 'includes/auth.php';
require_once 'classes/LoanManager.php';
require_once 'classes/CustomerManager.php';

requireLogin();

$loanManager = new LoanManager();
$customerManager = new CustomerManager();

// Get all loans with customer information
$query = "SELECT l.*, c.full_name, c.phone_number, c.email,
          DATEDIFF(l.due_date, CURDATE()) as days_remaining,
          CASE 
            WHEN l.status = 'active' AND l.due_date < CURDATE() THEN 'overdue'
            ELSE l.status
          END as display_status
          FROM loans l 
          JOIN customers c ON l.customer_id = c.id 
          ORDER BY l.created_at DESC";

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare($query);
$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans - SwiftCash Admin</title>
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
                <a href="loans.php" class="flex items-center px-6 py-3 text-white sidebar-active">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    Loans
                </a>
                <a href="payments.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
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
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Loan Management</h2>
                    <div class="flex items-center space-x-4">
                        <a href="new-loan.php" class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>New Loan
                        </a>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Loans Content -->
            <main class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center dark:bg-blue-900">
                                <i class="fas fa-money-bill-wave text-blue-600 text-xl dark:text-blue-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Loans</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($loans, function($loan) { return $loan['status'] === 'active'; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center dark:bg-green-900">
                                <i class="fas fa-check-circle text-green-600 text-xl dark:text-green-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($loans, function($loan) { return $loan['status'] === 'completed'; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center dark:bg-yellow-900">
                                <i class="fas fa-clock text-yellow-600 text-xl dark:text-yellow-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($loans, function($loan) { return $loan['status'] === 'pending'; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center dark:bg-red-900">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl dark:text-red-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overdue</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($loans, function($loan) { return $loan['display_status'] === 'overdue'; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 dark:bg-gray-800">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" id="searchLoans" placeholder="Search loans by customer name or loan ID..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <select id="filterStatus" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                <!-- Loans Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Loan ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Term</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" id="loansTableBody">
                                <?php foreach ($loans as $loan): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">SC<?= str_pad($loan['id'], 3, '0', STR_PAD_LEFT) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-bold"><?= substr($loan['full_name'], 0, 1) ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= $loan['full_name'] ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= $loan['phone_number'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">MWK <?= number_format($loan['loan_amount'], 2) ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Total: MWK <?= number_format($loan['total_payable'], 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= $loan['loan_term_days'] ?> days</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?= $loan['interest_rate'] ?>% interest</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'active' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        ];
                                        $statusClass = $statusColors[$loan['display_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                            <?= ucfirst($loan['display_status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= date('M j, Y', strtotime($loan['due_date'])) ?></div>
                                        <?php if ($loan['display_status'] === 'active'): ?>
                                            <div class="text-sm <?= $loan['days_remaining'] < 0 ? 'text-red-600' : ($loan['days_remaining'] <= 3 ? 'text-yellow-600' : 'text-gray-500') ?>">
                                                <?= $loan['days_remaining'] < 0 ? abs($loan['days_remaining']) . ' days overdue' : $loan['days_remaining'] . ' days left' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewLoan(<?= $loan['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($loan['status'] === 'pending'): ?>
                                            <button onclick="approveLoan(<?= $loan['id'] ?>)" class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loan['display_status'] === 'active'): ?>
                                            <button onclick="markPaid(<?= $loan['id'] ?>)" class="text-orange-600 hover:text-orange-900">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                        <?php endif; ?>
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

        // Loan actions
        function viewLoan(id) {
            window.location.href = `loan-details.php?id=${id}`;
        }

        function approveLoan(id) {
            if (confirm('Are you sure you want to approve this loan?')) {
                fetch('api/approve-loan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ loan_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Loan approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function markPaid(id) {
            if (confirm('Mark this loan as fully paid?')) {
                fetch('api/mark-paid.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ loan_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Loan marked as paid!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Search functionality
        document.getElementById('searchLoans').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#loansTableBody tr');
            
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
        document.getElementById('filterStatus').addEventListener('change', function(e) {
            const filterValue = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#loansTableBody tr');
            
            rows.forEach(row => {
                if (filterValue === '') {
                    row.style.display = '';
                } else {
                    const statusCell = row.querySelector('td:nth-child(5)');
                    const status = statusCell.textContent.toLowerCase().trim();
                    if (status.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>