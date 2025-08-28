<?php
require_once 'includes/auth.php';
require_once 'classes/EmailReminder.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get upcoming payments (next 7 days)
$query = "SELECT l.*, c.full_name, c.phone_number, c.email,
          DATEDIFF(l.due_date, CURDATE()) as days_remaining
          FROM loans l 
          JOIN customers c ON l.customer_id = c.id 
          WHERE l.status = 'active' 
          AND l.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          ORDER BY l.due_date ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overdue loans
$query = "SELECT l.*, c.full_name, c.phone_number, c.email,
          DATEDIFF(CURDATE(), l.due_date) as days_overdue
          FROM loans l 
          JOIN customers c ON l.customer_id = c.id 
          WHERE l.status = 'active' 
          AND l.due_date < CURDATE()
          ORDER BY l.due_date ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent email reminders
$query = "SELECT er.*, l.id as loan_id, c.full_name, c.email
          FROM email_reminders er
          JOIN loans l ON er.loan_id = l.id
          JOIN customers c ON l.customer_id = c.id
          ORDER BY er.sent_date DESC
          LIMIT 20";

$stmt = $db->prepare($query);
$stmt->execute();
$recent_reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders - SwiftCash Admin</title>
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
                <a href="payments.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-credit-card mr-3"></i>
                    Payments
                </a>
                <a href="reminders.php" class="flex items-center px-6 py-3 text-white sidebar-active">
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
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Email Reminders</h2>
                    <div class="flex items-center space-x-4">
                        <button onclick="sendManualReminders()" class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reminders
                        </button>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Reminders Content -->
            <main class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center dark:bg-yellow-900">
                                <i class="fas fa-clock text-yellow-600 text-xl dark:text-yellow-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Payments</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($upcoming) ?></p>
                                <p class="text-sm text-yellow-600">Next 7 days</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center dark:bg-red-900">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl dark:text-red-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overdue Loans</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($overdue) ?></p>
                                <p class="text-sm text-red-600">Requires attention</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center dark:bg-blue-900">
                                <i class="fas fa-envelope text-blue-600 text-xl dark:text-blue-300"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Emails Sent Today</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= count(array_filter($recent_reminders, function($r) { return date('Y-m-d', strtotime($r['sent_date'])) === date('Y-m-d'); })) ?>
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
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Success Rate</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">98%</p>
                                <p class="text-sm text-green-600">Email delivery</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Upcoming Payments -->
                    <div class="bg-white rounded-xl shadow-sm dark:bg-gray-800">
                        <div class="p-6 border-b dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Upcoming Payments (Next 7 Days)</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Customers who need payment reminders</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($upcoming)): ?>
                                <p class="text-gray-500 text-center py-8">No upcoming payments</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($upcoming as $payment): ?>
                                        <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg dark:bg-yellow-900/20">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white text-sm font-bold"><?= substr($payment['full_name'], 0, 1) ?></span>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900 dark:text-white"><?= $payment['full_name'] ?></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">SC<?= str_pad($payment['id'], 3, '0', STR_PAD_LEFT) ?> • <?= $payment['phone_number'] ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900 dark:text-white">MWK <?= number_format($payment['total_payable'], 2) ?></p>
                                                <p class="text-sm <?= $payment['days_remaining'] <= 1 ? 'text-red-600' : 'text-yellow-600' ?>">
                                                    <?= $payment['days_remaining'] ?> days left
                                                </p>
                                            </div>
                                            <button onclick="sendIndividualReminder(<?= $payment['id'] ?>)" class="ml-4 text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Overdue Loans -->
                    <div class="bg-white rounded-xl shadow-sm dark:bg-gray-800">
                        <div class="p-6 border-b dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Overdue Loans</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Loans requiring immediate attention</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($overdue)): ?>
                                <p class="text-gray-500 text-center py-8">No overdue loans</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($overdue as $loan): ?>
                                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg dark:bg-red-900/20">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-exclamation text-white"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900 dark:text-white"><?= $loan['full_name'] ?></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">SC<?= str_pad($loan['id'], 3, '0', STR_PAD_LEFT) ?> • <?= $loan['phone_number'] ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-red-600">MWK <?= number_format($loan['total_payable'], 2) ?></p>
                                                <p class="text-sm text-red-600"><?= $loan['days_overdue'] ?> days overdue</p>
                                                <?php if ($loan['days_overdue'] >= 7): ?>
                                                    <p class="text-xs text-red-800">Late fee applicable</p>
                                                <?php endif; ?>
                                            </div>
                                            <button onclick="sendOverdueReminder(<?= $loan['id'] ?>)" class="ml-4 text-red-600 hover:text-red-800">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Email Activity -->
                <div class="bg-white rounded-xl shadow-sm dark:bg-gray-800">
                    <div class="p-6 border-b dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Recent Email Activity</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Latest automated reminders sent</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Loan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Sent Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <?php foreach ($recent_reminders as $reminder): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-bold"><?= substr($reminder['full_name'], 0, 1) ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= $reminder['full_name'] ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= $reminder['email'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        SC<?= str_pad($reminder['loan_id'], 3, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $typeColors = [
                                            'upcoming' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'late_fee' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                        ];
                                        $typeClass = $typeColors[$reminder['reminder_type']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $typeClass ?>">
                                            <?= ucfirst(str_replace('_', ' ', $reminder['reminder_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?= date('M j, Y g:i A', strtotime($reminder['sent_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <?= ucfirst($reminder['email_status']) ?>
                                        </span>
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

        // Send manual reminders
        function sendManualReminders() {
            if (confirm('Send reminder emails to all customers with upcoming payments?')) {
                fetch('api/send-reminders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ type: 'all' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Successfully sent ${data.count} reminder emails!`);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Send individual reminder
        function sendIndividualReminder(loanId) {
            fetch('api/send-reminders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type: 'individual', loan_id: loanId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reminder sent successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Send overdue reminder
        function sendOverdueReminder(loanId) {
            fetch('api/send-reminders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type: 'overdue', loan_id: loanId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Overdue reminder sent successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>