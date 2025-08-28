<?php
require_once 'includes/auth.php';
require_once 'classes/LoanManager.php';
require_once 'classes/CustomerManager.php';

requireLogin();

$loanManager = new LoanManager();
$customerManager = new CustomerManager();

// Get dashboard statistics
$totalLoans = $loanManager->getTotalLoansIssued();
$recentlyRepaid = $loanManager->getRecentlyRepaidLoans(5);
$upcomingPayments = $loanManager->getUpcomingRepayments(7);
$overdueLoans = $loanManager->getOverdueLoans();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftCash Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-active { background: linear-gradient(135deg, #1A4E8A, #F5A623); }
        .card-hover:hover { transform: translateY(-2px); transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6 border-b">
                <h1 class="text-xl font-bold text-gray-800">SwiftCash Admin</h1>
                <p class="text-sm text-gray-600">Loan Management System</p>
            </div>
            
            <nav class="mt-6">
                <a href="index.php" class="flex items-center px-6 py-3 text-white sidebar-active">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="customers.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users mr-3"></i>
                    Customers
                </a>
                <a href="loans.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    Loans
                </a>
                <a href="payments.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-credit-card mr-3"></i>
                    Payments
                </a>
                <a href="reminders.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-bell mr-3"></i>
                    Reminders
                </a>
                <a href="reports.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Reports
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6 border-t">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold"><?= substr($_SESSION['admin_name'], 0, 1) ?></span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-800"><?= $_SESSION['admin_name'] ?></p>
                        <p class="text-xs text-gray-600"><?= ucfirst($_SESSION['admin_role']) ?></p>
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
            <header class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-800">Dashboard Overview</h2>
                    <div class="flex items-center space-x-4">
                        <button class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>New Loan
                        </button>
                        <div class="text-sm text-gray-600">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Loans Issued</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $totalLoans['total'] ?></p>
                                <p class="text-sm text-green-600">MWK <?= number_format($totalLoans['total_amount'] ?? 0, 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Recently Repaid</p>
                                <p class="text-2xl font-bold text-gray-900"><?= count($recentlyRepaid) ?></p>
                                <p class="text-sm text-green-600">This week</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Upcoming Payments</p>
                                <p class="text-2xl font-bold text-gray-900"><?= count($upcomingPayments) ?></p>
                                <p class="text-sm text-yellow-600">Next 7 days</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Overdue Loans</p>
                                <p class="text-2xl font-bold text-gray-900"><?= count($overdueLoans) ?></p>
                                <p class="text-sm text-red-600">Requires attention</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Upcoming Repayments -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Upcoming Repayments</h3>
                            <p class="text-sm text-gray-600">Loans due in the next 7 days</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($upcomingPayments)): ?>
                                <p class="text-gray-500 text-center py-8">No upcoming payments</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($upcomingPayments as $payment): ?>
                                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white text-sm font-bold"><?= substr($payment['full_name'], 0, 1) ?></span>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900"><?= $payment['full_name'] ?></p>
                                                    <p class="text-sm text-gray-600">Loan #SC<?= $payment['id'] ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">MWK <?= number_format($payment['total_payable'], 2) ?></p>
                                                <p class="text-sm <?= $payment['days_remaining'] <= 1 ? 'text-red-600' : 'text-yellow-600' ?>">
                                                    <?= $payment['days_remaining'] ?> days left
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recently Repaid Loans -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Recently Repaid</h3>
                            <p class="text-sm text-gray-600">Latest completed loans</p>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recentlyRepaid)): ?>
                                <p class="text-gray-500 text-center py-8">No recent repayments</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentlyRepaid as $repaid): ?>
                                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-check text-white"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900"><?= $repaid['full_name'] ?></p>
                                                    <p class="text-sm text-gray-600">Loan #SC<?= $repaid['id'] ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-green-600">MWK <?= number_format($repaid['total_payable'], 2) ?></p>
                                                <p class="text-sm text-gray-600"><?= date('M j, Y', strtotime($repaid['updated_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Overdue Loans Alert -->
                <?php if (!empty($overdueLoans)): ?>
                <div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-red-800">Overdue Loans Requiring Attention</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach (array_slice($overdueLoans, 0, 4) as $overdue): ?>
                            <div class="bg-white p-4 rounded-lg border border-red-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= $overdue['full_name'] ?></p>
                                        <p class="text-sm text-gray-600">Loan #SC<?= $overdue['id'] ?></p>
                                        <p class="text-sm text-red-600"><?= $overdue['days_overdue'] ?> days overdue</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-red-600">MWK <?= number_format($overdue['total_payable'], 2) ?></p>
                                        <?php if ($overdue['days_overdue'] >= 7): ?>
                                            <p class="text-xs text-red-800">Late fee applicable</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>