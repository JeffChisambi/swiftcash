<?php
require_once 'includes/auth.php';
require_once 'classes/CustomerManager.php';

requireLogin();

$customerManager = new CustomerManager();
$customers = $customerManager->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - SwiftCash Admin</title>
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
                <a href="customers.php" class="flex items-center px-6 py-3 text-white sidebar-active">
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
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Customer Management</h2>
                    <div class="flex items-center space-x-4">
                        <button onclick="openAddCustomerModal()" class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-2"></i>Add Customer
                        </button>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <?= date('l, F j, Y') ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Customers Content -->
            <main class="p-6">
                <!-- Search and Filter -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 dark:bg-gray-800">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" id="searchCustomers" placeholder="Search customers..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <select id="filterStatus" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Customers</option>
                            <option value="active">Active Loans</option>
                            <option value="completed">Completed Loans</option>
                            <option value="new">New Customers</option>
                        </select>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Employment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Loans</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Active Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" id="customersTableBody">
                                <?php foreach ($customers as $customer): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-orange-500 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold"><?= substr($customer['full_name'], 0, 1) ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= $customer['full_name'] ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">ID: <?= $customer['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= $customer['phone_number'] ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?= $customer['email'] ?: 'No email' ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white"><?= ucfirst($customer['employment_status'] ?: 'Not specified') ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">MWK <?= number_format($customer['monthly_income'] ?: 0, 0) ?>/month</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <?= $customer['total_loans'] ?> loans
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        MWK <?= number_format($customer['active_loan_amount'] ?: 0, 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewCustomer(<?= $customer['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editCustomer(<?= $customer['id'] ?>)" class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="newLoanForCustomer(<?= $customer['id'] ?>)" class="text-orange-600 hover:text-orange-900">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
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

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-2xl w-full p-6 dark:bg-gray-800">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add New Customer</h3>
                    <button onclick="closeAddCustomerModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="addCustomerForm">
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                            <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                            <input type="tel" name="phone_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employment Status</label>
                            <select name="employment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select status</option>
                                <option value="employed">Employed</option>
                                <option value="self-employed">Self-employed</option>
                                <option value="business-owner">Business owner</option>
                                <option value="student">Student</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                            <textarea name="address" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monthly Income (MWK)</label>
                            <input type="number" name="monthly_income" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeAddCustomerModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-lg hover:shadow-lg">
                            Add Customer
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
        function openAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.remove('hidden');
        }

        function closeAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.add('hidden');
        }

        // Customer actions
        function viewCustomer(id) {
            window.location.href = `customer-profile.php?id=${id}`;
        }

        function editCustomer(id) {
            // Implement edit functionality
            console.log('Edit customer:', id);
        }

        function newLoanForCustomer(id) {
            window.location.href = `new-loan.php?customer_id=${id}`;
        }

        // Search functionality
        document.getElementById('searchCustomers').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#customersTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add customer form submission
        document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add-customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Customer added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the customer.');
            });
        });
    </script>
</body>
</html>