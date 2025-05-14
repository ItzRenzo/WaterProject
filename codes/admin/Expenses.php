<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/expenses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="Sidebar.html">
</head>

<body>
    <div id="navbar"></div>

    <script>
        fetch('Sidebar.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('navbar').innerHTML = data;
            });
    </script>

    <div class="main-content">
        <!-- Header Section -->
        <div class="expenses-header">
            <h2>Expenses Management</h2>
            <button id="addExpenseBtn" class="add-expense-btn">
                <i class="fas fa-plus"></i> Add Expense
            </button>
        </div>

        <!-- Expense Statistics -->
        <div class="expense-stats">
            <div class="stat-card">
                <div class="stat-icon total-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-details">
                    <h3 id="totalExpenses">₱12,500</h3>
                    <p>Total Expenses (This Month)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon average-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-details">
                    <h3 id="dailyAverage">₱416.67</h3>
                    <p>Monthly Average</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon category-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-details">
                    <h3 id="highestCategory">Utilities</h3>
                    <p>Highest Expense Category</p>
                </div>
            </div>
        </div>

        <!-- Expense Graph -->
        <div class="expense-graph-container">
            <div class="card">
                <div class="card-header">
                    <h3>Monthly Expenses Overview</h3>
                    <div class="chart-filters">
                        <select id="chartFilter">
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Expense Listing -->
        <div class="card expense-listing">
            <div class="card-header">
                <h3>Expense Entries</h3>
                <div class="expense-filters">
                    <div class="search-bar">
                        <input type="text" id="expenseSearchInput" placeholder="Search expenses...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="date-filter">
                        <label>From</label>
                        <input type="date" id="fromDate">
                        <label>To</label>
                        <input type="date" id="toDate">
                        <button class="filter-btn" id="applyDateFilter">Apply</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>Expense Name</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody">
                            <!-- Data will be populated here by JS -->
                        </tbody>
                    </table>
                    <div id="pagination" class="pagination"></div>
                    <div id="expenseCount" class="expense-count" style="margin-top:10px;color:var(--text-light);font-size:15px;"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Add Expense Modal -->
    <div class="modal" id="addExpenseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Expense</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="expenseForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expenseName">Expense Name</label>
                            <input type="text" id="expenseName" placeholder="Enter expense name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expenseCategory">Category</label>
                            <select id="expenseCategory" required>
                                <option value="">Select Category</option>
                                <option value="utilities">Utilities</option>
                                <option value="supplies">Supplies</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="salaries">Salaries</option>
                                <option value="rent">Rent</option>
                                <option value="transportation">Transportation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="expenseAmount">Amount</label>
                            <div class="amount-input">
                                <span class="currency"></span>
                                <input type="number" id="expenseAmount" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expenseDate">Date</label>
                            <input type="date" id="expenseDate" required>
                        </div>
                        <div class="form-group">
                            <label for="paymentMethod">Payment Method</label>
                            <select id="paymentMethod">
                                <option value="cash">Cash</option>
                                <option value="Gcash">Gcash</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="expenseDescription">Description (Optional)</label>
                        <textarea id="expenseDescription" rows="3"
                            placeholder="Add details about this expense"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div class="modal" id="editExpenseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Expense</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm">
                    <input type="hidden" id="editExpenseId">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editExpenseName">Expense Name</label>
                            <input type="text" id="editExpenseName" placeholder="Enter expense name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="editExpenseCategory">Category</label>
                            <select id="editExpenseCategory" required>
                                <option value="">Select Category</option>
                                <option value="utilities">Utilities</option>
                                <option value="supplies">Supplies</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="salaries">Salaries</option>
                                <option value="rent">Rent</option>
                                <option value="transportation">Transportation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editExpenseAmount">Amount</label>
                            <div class="amount-input">
                                <span class="currency"></span>
                                <input type="number" id="editExpenseAmount" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="editExpenseDate">Date</label>
                            <input type="date" id="editExpenseDate" required>
                        </div>
                        <div class="form-group">
                            <label for="editPaymentMethod">Payment Method</label>
                            <select id="editPaymentMethod">
                                <option value="cash">Cash</option>
                                <option value="Gcash">Gcash</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editExpenseDescription">Description (Optional)</label>
                        <textarea id="editExpenseDescription" rows="3"
                            placeholder="Add details about this expense"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Update Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteConfirmModal">
        <div class="modal-content">
            <div class="modal-header delete-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Expense</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
                <div class="expense-to-delete">
                    <p class="expense-name">Expense Name: <span id="deleteExpenseName">Electricity Bill</span></p>
                    <p class="expense-amount">Amount: <span id="deleteExpenseAmount">₱3,250.00</span></p>
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="delete-btn"><i class="fas fa-trash"></i>
                        Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // DOM Elements
            const addExpenseBtn = document.getElementById('addExpenseBtn');
            const addExpenseModal = document.getElementById('addExpenseModal');
            const editExpenseModal = document.getElementById('editExpenseModal');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const closeButtons = document.querySelectorAll('.close-btn');
            const cancelButtons = document.querySelectorAll('.cancel-btn');
            const editButtons = document.querySelectorAll('.edit-btn');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const expenseForm = document.getElementById('expenseForm');
            const editExpenseForm = document.getElementById('editExpenseForm');

            // Set today's date as the default for the expense date input
            document.getElementById('expenseDate').valueAsDate = new Date();

            // Open Add Expense Modal
            addExpenseBtn.addEventListener('click', function () {
                addExpenseModal.classList.add('show');
            });

            // Close Modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function () {
                    addExpenseModal.classList.remove('show');
                    editExpenseModal.classList.remove('show');
                    deleteConfirmModal.classList.remove('show');
                });
            });

            cancelButtons.forEach(button => {
                button.addEventListener('click', function () {
                    addExpenseModal.classList.remove('show');
                    editExpenseModal.classList.remove('show');
                    deleteConfirmModal.classList.remove('show');
                });
            });

            // Close on outside click
            window.addEventListener('click', function (e) {
                if (e.target === addExpenseModal) {
                    addExpenseModal.classList.remove('show');
                }
                if (e.target === editExpenseModal) {
                    editExpenseModal.classList.remove('show');
                }
                if (e.target === deleteConfirmModal) {
                    deleteConfirmModal.classList.remove('show');
                }
            });

            // Handle Edit Button Click
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Get expense ID
                    const expenseId = this.getAttribute('data-id');

                    // Get the table row
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const category = row.cells[1].textContent.toLowerCase();
                    const amount = row.cells[2].textContent.replace('₱', '').replace(',', '');
                    const date = row.cells[3].textContent;

                    // Set form values
                    document.getElementById('editExpenseId').value = expenseId;
                    document.getElementById('editExpenseName').value = name;
                    document.getElementById('editExpenseCategory').value =
                        category === 'utilities' ? 'utilities' :
                            category === 'supplies' ? 'supplies' :
                                category === 'maintenance' ? 'maintenance' : 'other';
                    document.getElementById('editExpenseAmount').value = parseFloat(amount);

                    // Convert date to yyyy-mm-dd format for input
                    const dateParts = date.split(', ');
                    const monthDay = dateParts[0].split(' ');
                    const month = new Date(Date.parse(monthDay[0] + " 1, 2000")).getMonth() + 1;
                    const day = parseInt(monthDay[1]);
                    const year = parseInt(dateParts[1]);
                    document.getElementById('editExpenseDate').value =
                        `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

                    // Show modal
                    editExpenseModal.classList.add('show');
                });
            });

            // Handle Delete Button Click
            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Get expense ID
                    const expenseId = this.getAttribute('data-id');

                    // Get the table row
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const amount = row.cells[2].textContent;

                    // Set confirmation details (add (delete) to name)
                    document.getElementById('deleteExpenseName').textContent = name + ' (delete)';
                    document.getElementById('deleteExpenseAmount').textContent = amount;

                    // Store expense ID for deletion
                    confirmDeleteBtn.setAttribute('data-id', expenseId);

                    // Show modal
                    deleteConfirmModal.classList.add('show');
                });
            });

            // Handle Confirm Delete
            confirmDeleteBtn.addEventListener('click', function () {
                const expenseId = this.getAttribute('data-id');
                // Send AJAX request to mark the expense type as (delete)
                fetch('delete_expense.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(expenseId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh the table
                        fetchExpenses();
                        showNotification('Expense marked as deleted');
                    } else {
                        showNotification('Failed to delete expense');
                    }
                });
                // Close the modal
                deleteConfirmModal.classList.remove('show');
            });

            // Handle Add Expense Form Submit
            expenseForm.addEventListener('submit', function (e) {
                e.preventDefault();

                // Get form values
                const name = document.getElementById('expenseName').value;
                const category = document.getElementById('expenseCategory').value;
                const amount = document.getElementById('expenseAmount').value;
                const date = document.getElementById('expenseDate').value;
                const paymentMethod = document.getElementById('paymentMethod').value;
                const description = document.getElementById('expenseDescription').value;

                // Send to backend
                fetch('add_expense.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        category,
                        amount,
                        date,
                        paymentMethod,
                        description
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchExpenses();
                        showNotification('Expense added successfully');
                        expenseForm.reset();
                        document.getElementById('expenseDate').valueAsDate = new Date();
                        addExpenseModal.classList.remove('show');
                    } else {
                        showNotification('Failed to add expense');
                    }
                });
            });

            // Handle Edit Expense Form Submit
            editExpenseForm.addEventListener('submit', function (e) {
                e.preventDefault();

                // Get form values
                const expenseId = document.getElementById('editExpenseId').value;
                const name = document.getElementById('editExpenseName').value;
                const category = document.getElementById('editExpenseCategory').value;
                const amount = document.getElementById('editExpenseAmount').value;
                const date = document.getElementById('editExpenseDate').value;
                const paymentMethod = document.getElementById('editPaymentMethod').value;
                const description = document.getElementById('editExpenseDescription').value;

                // Send to backend
                fetch('edit_expense.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: expenseId,
                        name,
                        category,
                        amount,
                        date,
                        paymentMethod,
                        description
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchExpenses();
                        showNotification('Expense updated successfully');
                        editExpenseModal.classList.remove('show');
                    } else {
                        showNotification('Failed to update expense');
                    }
                });
            });

            // Function to add new expense to the table
            function addExpenseToTable(name, category, amount, date) {
                const table = document.querySelector('.expense-table tbody');
                const newRow = table.insertRow(0);

                // Format the date for display
                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Create a unique ID for the new row
                const newId = Date.now();

                // Format the category for display (capitalize first letter)
                const displayCategory = category.charAt(0).toUpperCase() + category.slice(1);

                newRow.innerHTML = `
                    <td>${name}</td>
                    <td>${displayCategory}</td>
                    <td>₱${parseFloat(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${formattedDate}</td>
                    <td class="actions">
                        <button class="edit-btn" data-id="${newId}"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn" data-id="${newId}"><i class="fas fa-trash"></i></button>
                    </td>
                `;

                // Add event listeners to the new buttons
                const newEditBtn = newRow.querySelector('.edit-btn');
                const newDeleteBtn = newRow.querySelector('.delete-btn');

                newEditBtn.addEventListener('click', function () {
                    // Use the same logic as for existing edit buttons
                    const expenseId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const category = row.cells[1].textContent.toLowerCase();
                    const amount = row.cells[2].textContent.replace('₱', '').replace(',', '');
                    const date = row.cells[3].textContent;

                    document.getElementById('editExpenseId').value = expenseId;
                    document.getElementById('editExpenseName').value = name;
                    document.getElementById('editExpenseCategory').value =
                        category === 'utilities' ? 'utilities' :
                            category === 'supplies' ? 'supplies' :
                                category === 'maintenance' ? 'maintenance' : 'other';
                    document.getElementById('editExpenseAmount').value = parseFloat(amount);

                    const dateParts = date.split(', ');
                    const monthDay = dateParts[0].split(' ');
                    const month = new Date(Date.parse(monthDay[0] + " 1, 2000")).getMonth() + 1;
                    const day = parseInt(monthDay[1]);
                    const year = parseInt(dateParts[1]);
                    document.getElementById('editExpenseDate').value =
                        `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

                    editExpenseModal.classList.add('show');
                });

                newDeleteBtn.addEventListener('click', function () {
                    const expenseId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const amount = row.cells[2].textContent;

                    document.getElementById('deleteExpenseName').textContent = name + ' (delete)';
                    document.getElementById('deleteExpenseAmount').textContent = amount;
                    confirmDeleteBtn.setAttribute('data-id', expenseId);

                    deleteConfirmModal.classList.add('show');
                });
            }

            // Function to update existing expense in the table
            function updateExpenseInTable(id, name, category, amount, date) {
                const row = document.querySelector(`.edit-btn[data-id="${id}"]`).closest('tr');

                // Format the date for display
                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Format the category for display (capitalize first letter)
                const displayCategory = category.charAt(0).toUpperCase() + category.slice(1);

                row.cells[0].textContent = name;
                row.cells[1].textContent = displayCategory;
                row.cells[2].textContent = `₱${parseFloat(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                row.cells[3].textContent = formattedDate;
            }

            // Show notification function
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-check-circle"></i>
                        <p>${message}</p>
                    </div>
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);

                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }

            // Initialize Chart
            const ctx = document.getElementById('expenseChart').getContext('2d');
            const expenseChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Utilities', 'Supplies', 'Maintenance', 'Salaries', 'Rent', 'Transportation', 'Other'],
                    datasets: [{
                        label: 'Expenses by Category (₱)',
                        data: [6250, 850, 2500, 0, 0, 2900, 0],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 205, 86, 0.6)',
                            'rgba(201, 203, 207, 0.6)'
                        ],
                        borderColor: [
                            'rgb(54, 162, 235)',
                            'rgb(75, 192, 192)',
                            'rgb(255, 159, 64)',
                            'rgb(153, 102, 255)',
                            'rgb(255, 99, 132)',
                            'rgb(255, 205, 86)',
                            'rgb(201, 203, 207)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (₱)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Expenses by Category (May 2025)',
                            font: {
                                size: 16
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Update chart on filter change
            document.getElementById('chartFilter').addEventListener('change', function () {
                // Here you would normally fetch new data based on the selected period
                // For now, we'll just change the title

                let titleText;
                let newData;

                switch (this.value) {
                    case 'month':
                        titleText = 'Expenses by Category (May 2025)';
                        newData = [6250, 850, 2500, 0, 0, 2900, 0];
                        break;
                    case 'quarter':
                        titleText = 'Expenses by Category (Q2 2025)';
                        newData = [15250, 3850, 5500, 48000, 30000, 8900, 2500];
                        break;
                    case 'year':
                        titleText = 'Expenses by Category (2025)';
                        newData = [45250, 12850, 18500, 192000, 120000, 35900, 8500];
                        break;
                }

                expenseChart.options.plugins.title.text = titleText;
                expenseChart.data.datasets[0].data = newData;
                expenseChart.update();
            });
        });

        function updateExpenseStats() {
            // Get current month and year
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();
            // Filter expenses for current month
            const thisMonthExpenses = allExpenses.filter(exp => {
                const d = new Date(exp.ExpenseDate);
                return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
            });
            // Total
            const total = thisMonthExpenses.reduce((sum, exp) => sum + parseFloat(exp.Amount), 0);
            document.getElementById('totalExpenses').textContent = `₱${total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            // Monthly Average (divide by number of days in month)
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const avg = total / daysInMonth;
            document.getElementById('dailyAverage').textContent = `₱${avg.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            // Highest Expense Category
            const categoryTotals = {};
            thisMonthExpenses.forEach(exp => {
                const cat = exp.Category;
                categoryTotals[cat] = (categoryTotals[cat] || 0) + parseFloat(exp.Amount);
            });
            let highestCat = 'N/A';
            let highestVal = 0;
            for (const cat in categoryTotals) {
                if (categoryTotals[cat] > highestVal) {
                    highestVal = categoryTotals[cat];
                    highestCat = cat.charAt(0).toUpperCase() + cat.slice(1);
                }
            }
            document.getElementById('highestCategory').textContent = highestCat;
        }

        let allExpenses = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        function renderTablePage(page) {
            const tbody = document.getElementById('expenseTableBody');
            tbody.innerHTML = '';
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = allExpenses.slice(start, end);
            pageData.forEach(exp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${exp.TypeName}</td>
                    <td>${capitalizeFirst(exp.Category)}</td>
                    <td>₱${parseFloat(exp.Amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>${formatDate(exp.ExpenseDate)}</td>
                    <td>${exp.Description ? exp.Description : ''}</td>
                    <td class="actions">
                        <button class="edit-btn" data-id="${exp.ExpensesID}"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn" data-id="${exp.ExpensesID}"><i class="fas fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            attachExpenseRowEvents();
            renderPagination();
            // Show count below table
            const countDiv = document.getElementById('expenseCount');
            countDiv.textContent = `Expense${allExpenses.length === 1 ? '' : 's'} ${allExpenses.length} found`;
            // Update stats
            updateExpenseStats();
        }

        function renderPagination() {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            const totalPages = Math.ceil(allExpenses.length / rowsPerPage);
            if (totalPages <= 1) return;
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = (i === currentPage) ? 'active' : '';
                btn.addEventListener('click', function () {
                    currentPage = i;
                    renderTablePage(currentPage);
                });
                pagination.appendChild(btn);
            }
        }

        function fetchExpenses(filters = {}) {
            let url = 'get_expenses.php';
            const params = [];
            if (filters.from) params.push('from=' + encodeURIComponent(filters.from));
            if (filters.to) params.push('to=' + encodeURIComponent(filters.to));
            if (filters.search) params.push('search=' + encodeURIComponent(filters.search));
            if (params.length) url += '?' + params.join('&');
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    allExpenses = data;
                    currentPage = 1;
                    renderTablePage(currentPage);
                    // updateExpenseStats(); // Already called in renderTablePage
                });
        }

        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function attachExpenseRowEvents() {
            // Re-attach edit/delete events for new rows
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    // Use the same logic as for existing edit buttons
                    const expenseId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const category = row.cells[1].textContent.toLowerCase();
                    const amount = row.cells[2].textContent.replace('₱', '').replace(',', '');
                    const date = row.cells[3].textContent;

                    document.getElementById('editExpenseId').value = expenseId;
                    document.getElementById('editExpenseName').value = name;
                    document.getElementById('editExpenseCategory').value =
                        category === 'utilities' ? 'utilities' :
                            category === 'supplies' ? 'supplies' :
                                category === 'maintenance' ? 'maintenance' : 'other';
                    document.getElementById('editExpenseAmount').value = parseFloat(amount);

                    const dateParts = date.split(', ');
                    const monthDay = dateParts[0].split(' ');
                    const month = new Date(Date.parse(monthDay[0] + " 1, 2000")).getMonth() + 1;
                    const day = parseInt(monthDay[1]);
                    const year = parseInt(dateParts[1]);
                    document.getElementById('editExpenseDate').value =
                        `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

                    editExpenseModal.classList.add('show');
                });
            });
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const expenseId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const name = row.cells[0].textContent;
                    const amount = row.cells[2].textContent;

                    document.getElementById('deleteExpenseName').textContent = name + ' (delete)';
                    document.getElementById('deleteExpenseAmount').textContent = amount;
                    confirmDeleteBtn.setAttribute('data-id', expenseId);

                    deleteConfirmModal.classList.add('show');
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            fetchExpenses();
            document.getElementById('applyDateFilter').addEventListener('click', function() {
                const from = document.getElementById('fromDate').value;
                const to = document.getElementById('toDate').value;
                fetchExpenses({from, to});
            });
            document.getElementById('expenseSearchInput').addEventListener('input', function() {
                fetchExpenses({search: this.value});
            });
        });
    </script>
</body>

</html>