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
    <title>Orders - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/order_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
        <div class="orders-header">
            <h2>Order Management</h2>
        </div>

        <div class="orders-grid">
            <!-- Order Status Cards -->
            <div class="status-card">
                <div class="status-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="status-info">
                    <h3>Pending Orders</h3>
                    <p>25</p>
                </div>
            </div>

            <div class="status-card">
                <div class="status-icon processing">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="status-info">
                    <h3>Processing</h3>
                    <p>12</p>
                </div>
            </div>

            <div class="status-card">
                <div class="status-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-info">
                    <h3>Completed</h3>
                    <p>156</p>
                </div>
            </div>
        </div>

        <!-- Orders Table Section -->
        <div class="orders-table-container">
            <div class="table-header">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search orders...">
                </div>
                <div class="table-filters">
                    <select class="status-filter">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select class="date-filter">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Sample Order Row -->
                    <tr>
                        <td>#ORD001</td>
                        <td>John Doe</td>
                        <td>Mineral Water (x3)</td>
                        <td>₱450.00</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td class="actions">
                            <button class="view-btn"><i class="fas fa-eye"></i></button>
                            <button class="edit-btn"><i class="fas fa-edit"></i></button>
                            <button class="delete-btn"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>