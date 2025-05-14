<?php
require_once '../../Database/db_config.php';

// Filter logic for status and date
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';

$where = [];
$where[] = "(t.DeliveryMethod = 'delivery' OR t.DeliveryMethod = 'Delivery')";
$where[] = "LOWER(t.DeliveryStatus) != 'deleted'";

// Handle status filter with proper case matching
if ($statusFilter !== 'all') {
    // Convert first letter to uppercase for proper matching
    $status = ucfirst($statusFilter);
    $where[] = "t.DeliveryStatus = '" . $conn->real_escape_string($status) . "'";
}

// Date filter logic
if ($dateFilter !== 'all') {
    if ($dateFilter === 'today') {
        $where[] = "DATE(t.TransactionDate) = CURDATE()";
    } elseif ($dateFilter === 'week') {
        $where[] = "YEARWEEK(DATE(t.TransactionDate), 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($dateFilter === 'month') {
        $where[] = "YEAR(DATE(t.TransactionDate)) = YEAR(CURDATE()) AND MONTH(DATE(t.TransactionDate)) = MONTH(CURDATE())";
    }
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT t.TransactionID, t.DeliveryStatus, t.DeliveryMethod, t.TransactionDate, 
               t.Price, t.Quantity, c.CustomerName, c.CustomerAddress, c.CustomerNumber
        FROM Transaction t
        JOIN Customer c ON t.CustomerID = c.CustomerID
        $whereClause
        ORDER BY t.TransactionDate DESC";

$result = $conn->query($sql);
$deliveries = [];
$pendingCount = 0;
$deliveredCount = 0;
$cancelledCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
        $status = strtolower($row['DeliveryStatus']);
        if ($status === 'pending') $pendingCount++;
        if ($status === 'delivered') $deliveredCount++;
        if ($status === 'cancelled') $cancelledCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/deliveries.css">
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
        <!-- Header Section -->
        <div class="deliveries-header">
            <h2>Delivery Management</h2>
        </div>

        <!-- Delivery Statistics -->
        <div class="delivery-stats">
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <p><?php echo $pendingCount; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon delivered">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Delivered</h3>
                    <p><?php echo $deliveredCount; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon cancelled">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Cancelled</h3>
                    <p><?php echo $cancelledCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Deliveries Table -->
        <div class="deliveries-table-container">
            <div class="table-header">
                <div class="search-filter">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search deliveries...">
                    </div>
                    <div class="filters">  
                        <select class="status-filter" id="statusFilter">
                            <option value="all" <?php if($statusFilter==='all') echo 'selected'; ?>>All Status</option>
                            <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>Pending</option>
                            <option value="assigned" <?php if($statusFilter==='assigned') echo 'selected'; ?>>Assigned</option>
                            <option value="in transit" <?php if($statusFilter==='in transit') echo 'selected'; ?>>In Transit</option>
                            <option value="delivered" <?php if($statusFilter==='delivered') echo 'selected'; ?>>Delivered</option>
                            <option value="cancelled" <?php if($statusFilter==='cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                        <select class="date-filter" id="dateFilter">
                            <option value="today" <?php if($dateFilter==='today') echo 'selected'; ?>>Today</option>
                            <option value="week" <?php if($dateFilter==='week') echo 'selected'; ?>>This Week</option>
                            <option value="month" <?php if($dateFilter==='month') echo 'selected'; ?>>This Month</option>
                            <option value="all" <?php if($dateFilter==='all') echo 'selected'; ?>>All Time</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="deliveries-table">
                    <thead>
                        <tr>
                            <th>Delivery ID</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deliveriesTableBody">
                        <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td>#DEL<?= htmlspecialchars($delivery['TransactionID']) ?></td>
                            <td>#ORD<?= htmlspecialchars($delivery['TransactionID']) ?></td>
                            <td><?= htmlspecialchars($delivery['CustomerName']) ?></td>
                            <td><?= htmlspecialchars($delivery['CustomerAddress']) ?></td>
                            <td><span class="status-badge <?= strtolower(str_replace(' ', '-', $delivery['DeliveryStatus'])) ?>"><?= htmlspecialchars($delivery['DeliveryStatus']) ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="edit-btn" title="Edit" data-id="<?= htmlspecialchars($delivery['TransactionID']) ?>"><i class="fas fa-edit"></i></button>
                                    <button class="delete-btn" title="Delete" data-id="<?= htmlspecialchars($delivery['TransactionID']) ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assign Delivery Modal -->
    <div class="modal" id="assignDeliveryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Delivery</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="assignDeliveryForm">
                    <div class="form-group">
                        <label for="orderSelect">Select Order</label>
                        <select id="orderSelect" required>
                            <option value="">Choose an order</option>
                            <option value="1">#ORD241 - John Doe (3 items)</option>
                            <option value="2">#ORD242 - Maria Santos (1 item)</option>
                            <option value="3">#ORD243 - Robert Lee (5 items)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="driverSelect">Assign Driver</label>
                        <select id="driverSelect" required>
                            <option value="">Select driver</option>
                            <option value="1">Juan Dela Cruz</option>
                            <option value="2">Pedro Santos</option>
                            <option value="3">Maria Garcia</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="deliveryDate">Delivery Date</label>
                            <input type="date" id="deliveryDate" required>
                        </div>
                        <div class="form-group">
                            <label for="deliveryTime">Delivery Time</label>
                            <input type="time" id="deliveryTime" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deliveryNote">Delivery Notes (Optional)</label>
                        <textarea id="deliveryNote" placeholder="Special instructions for the driver"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="submit-btn">Assign Delivery</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Delivery Modal -->
    <div class="modal" id="editDeliveryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Delivery</h3>
                <button class="close-btn" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editDeliveryForm">
                    <input type="hidden" id="editTransactionID">
                    <div class="form-group">
                        <label for="editCustomerAddress">Customer Address</label>
                        <input type="text" id="editCustomerAddress" required>
                    </div>
                    <div class="form-group">
                        <label for="editDeliveryStatus">Delivery Status</label>
                        <select id="editDeliveryStatus">
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelEditModal">Cancel</button>
                        <button type="submit" class="submit-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delivery Detail Modal -->
    <div class="modal" id="deliveryDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delivery Details</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-section">
                    <h4>Delivery Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Delivery ID:</span>
                            <span class="value">#DEL001</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Status:</span>
                            <span class="value status in-transit">In Transit</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Scheduled Time:</span>
                            <span class="value">May 15, 2023 2:30 PM</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Estimated Arrival:</span>
                            <span class="value">In 20 minutes</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Order Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Order ID:</span>
                            <span class="value">#ORD237</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Order Date:</span>
                            <span class="value">May 15, 2023</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Items:</span>
                            <span class="value">3</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Total:</span>
                            <span class="value">₱435.00</span>
                        </div>
                    </div>

                    <div class="order-items">
                        <div class="order-item">
                            <div>Mineral Water (Round Container)</div>
                            <div>x2</div>
                        </div>
                        <div class="order-item">
                            <div>Alkaline Water (Slim Container)</div>
                            <div>x1</div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Customer Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Name:</span>
                            <span class="value">John Doe</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Contact:</span>
                            <span class="value">+63 912 345 6789</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Address:</span>
                            <span class="value">123 Main St, Manila</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Delivery Staff</h4>
                    <div class="delivery-staff">
                        <div class="staff-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="staff-details">
                            <h5>Juan Dela Cruz</h5>
                            <p>Driver ID: DRV001</p>
                            <p>Contact: +63 917 123 4567</p>
                        </div>
                    </div>
                </div>

                <div class="detail-actions">
                    <button class="status-btn pending">Mark as Pending</button>
                    <button class="status-btn in-transit">Mark as In Transit</button>
                    <button class="status-btn delivered">Mark as Delivered</button>
                    <button class="status-btn cancelled">Mark as Cancelled</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for modal functionality -->
    <script>
        // DOM Elements
        const assignDeliveryBtn = document.querySelector('.assign-delivery-btn');
        const assignDeliveryModal = document.getElementById('assignDeliveryModal');
        const deliveryDetailModal = document.getElementById('deliveryDetailModal');
        const editModal = document.getElementById('editDeliveryModal');
        const editForm = document.getElementById('editDeliveryForm');
        const closeEditModalBtn = document.getElementById('closeEditModal');
        const cancelEditModalBtn = document.getElementById('cancelEditModal');
        const closeButtons = document.querySelectorAll('.close-btn');
        const cancelButtons = document.querySelectorAll('.cancel-btn');
        const viewButtons = document.querySelectorAll('.view-btn');
        const updateButtons = document.querySelectorAll('.update-btn');
        let currentEditRow = null;

        // Open Assign Delivery Modal
        if (assignDeliveryBtn && assignDeliveryModal) {
            assignDeliveryBtn.addEventListener('click', function () {
                assignDeliveryModal.classList.add('show');
            });
        }

        // Open Delivery Detail Modal
        if (viewButtons && deliveryDetailModal) {
            viewButtons.forEach(button => {
                button.addEventListener('click', function () {
                    deliveryDetailModal.classList.add('show');
                });
            });
        }

        // Update Status Buttons
        if (updateButtons && deliveryDetailModal) {
            updateButtons.forEach(button => {
                button.addEventListener('click', function () {
                    deliveryDetailModal.classList.add('show');
                });
            });
        }

        // Close Modals
        if (closeButtons) {
            closeButtons.forEach(button => {
                button.addEventListener('click', function () {
                    if (assignDeliveryModal) assignDeliveryModal.classList.remove('show');
                    if (deliveryDetailModal) deliveryDetailModal.classList.remove('show');
                    if (editModal) editModal.classList.remove('show');
                });
            });
        }

        if (cancelButtons) {
            cancelButtons.forEach(button => {
                button.addEventListener('click', function () {
                    if (assignDeliveryModal) assignDeliveryModal.classList.remove('show');
                    if (deliveryDetailModal) deliveryDetailModal.classList.remove('show');
                    if (editModal) editModal.classList.remove('show');
                });
            });
        }

        // Close on outside click
        window.addEventListener('click', function (e) {
            if (e.target === assignDeliveryModal) {
                assignDeliveryModal.classList.remove('show');
            }
            if (e.target === deliveryDetailModal) {
                deliveryDetailModal.classList.remove('show');
            }
            if (e.target === editModal) {
                editModal.classList.remove('show');
            }
        });

        // Form submission
        const assignDeliveryForm = document.getElementById('assignDeliveryForm');
        assignDeliveryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Get form data
            const orderSelect = document.getElementById('orderSelect');
            const driverSelect = document.getElementById('driverSelect');
            const deliveryDate = document.getElementById('deliveryDate');
            const deliveryTime = document.getElementById('deliveryTime');

            // Create new delivery and add to table
            const orderText = orderSelect.options[orderSelect.selectedIndex].text;
            const orderId = orderText.split(' - ')[0];
            const customerName = orderText.split(' - ')[1].split(' (')[0];
            const driverName = driverSelect.options[driverSelect.selectedIndex].text;

            // Create new delivery ID
            const deliveryId = '#DEL' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');

            // Format delivery time
            const deliveryDateTime = new Date(`${deliveryDate.value}T${deliveryTime.value}`);
            const formattedTime = deliveryDateTime.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            // Add new row to table
            const tableBody = document.querySelector('.deliveries-table tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${deliveryId}</td>
                <td>${orderId}</td>
                <td>${customerName}</td>
                <td>Customer address here</td>
                <td>${driverName}</td>
                <td>
                    <div class="action-buttons">
                        <button class="edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            `;

            tableBody.prepend(newRow);

            // Update pending count
            const pendingCount = document.querySelector('.stat-card:first-child .stat-info p');
            pendingCount.textContent = parseInt(pendingCount.textContent) + 1;

            // Close modal and reset form
            assignDeliveryModal.classList.remove('show');
            assignDeliveryForm.reset();

            // Add event listeners to new buttons
            const newViewBtn = newRow.querySelector('.view-btn');
            const newUpdateBtn = newRow.querySelector('.update-btn');
            const newEditBtn = newRow.querySelector('.edit-btn');
            const newDeleteBtn = newRow.querySelector('.delete-btn');

            newViewBtn.addEventListener('click', function () {
                deliveryDetailModal.classList.add('show');
            });

            newUpdateBtn.addEventListener('click', function () {
                deliveryDetailModal.classList.add('show');
            });

            newEditBtn.addEventListener('click', function () {
                const id = newEditBtn.dataset.id;
                alert('Edit delivery ' + id);
            });

            newDeleteBtn.addEventListener('click', function () {
                const id = newDeleteBtn.dataset.id;
                if (confirm('Are you sure you want to delete this delivery?')) {
                    alert('Delete delivery ' + id);
                }
            });

            // Show success message
            showNotification('Delivery assigned successfully!');
        });

        // Delivery Edit and Delete actions
        document.addEventListener('click', function(e) {
            // Edit button
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const row = btn.closest('tr');
                currentEditRow = row;
                // Get data from row
                const transactionId = btn.dataset.id;
                const address = row.children[3].textContent.trim();
                const status = row.querySelector('.status-badge').textContent.trim().toLowerCase();
                // Populate modal
                document.getElementById('editTransactionID').value = transactionId;
                document.getElementById('editCustomerAddress').value = address;
                document.getElementById('editDeliveryStatus').value = status;
                // Show modal
                editModal.classList.add('show');
            }
            // Delete button
            if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const row = btn.closest('tr');
                const transactionId = btn.dataset.id;
                if (confirm('Are you sure you want to delete this delivery?')) {
                    fetch('../Controllers/update_order_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transaction_id: transactionId, status: 'deleted' })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.style.display = 'none';
                            showNotification('Delivery deleted successfully!');
                        } else {
                            alert('Failed to delete delivery.');
                        }
                    });
                }
            }
        });

        // Close edit modal
        closeEditModalBtn.addEventListener('click', function() {
            editModal.classList.remove('show');
        });
        cancelEditModalBtn.addEventListener('click', function() {
            editModal.classList.remove('show');
        });

        // Save edit changes
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const transactionId = document.getElementById('editTransactionID').value;
            const address = document.getElementById('editCustomerAddress').value;
            const status = document.getElementById('editDeliveryStatus').value;
            fetch('../Controllers/update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transaction_id: transactionId, status: status, address: address })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update row in table
                    if (currentEditRow) {
                        currentEditRow.children[3].textContent = address;
                        currentEditRow.querySelector('.status-badge').textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        // Hide if status is deleted
                        if (status === 'deleted') currentEditRow.style.display = 'none';
                    }
                    editModal.classList.remove('show');
                    showNotification('Delivery updated successfully!');
                } else {
                    alert('Failed to update delivery.');
                }
            });
        });

        // Notification function
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="notification-message">${message}</div>
                <button class="notification-close"><i class="fas fa-times"></i></button>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);

            // Close button
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            });
        }

        // Filter logic for status, date, and search
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const dateFilter = document.getElementById('dateFilter');
            const tableBody = document.getElementById('deliveriesTableBody');
            const searchInput = document.querySelector('.search-bar input');
            function filterTable() {
                const status = statusFilter.value;
                const date = dateFilter.value;
                const search = searchInput.value.trim().toLowerCase();
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const statusBadge = row.querySelector('.status-badge');
                    const statusText = statusBadge ? statusBadge.textContent.trim().toLowerCase() : '';
                    const customer = row.children[2]?.textContent.trim().toLowerCase() || '';
                    const address = row.children[3]?.textContent.trim().toLowerCase() || '';
                    const orderId = row.children[1]?.textContent.trim().toLowerCase() || '';
                    let show = true;
                    if (status !== 'all' && statusText !== status.toLowerCase()) {
                        show = false;
                    }
                    // Simple search: match in customer, address, orderId, or status
                    if (show && search) {
                        if (!customer.includes(search) && !address.includes(search) && !orderId.includes(search) && !statusText.includes(search)) {
                            show = false;
                        }
                    }
                    row.style.display = show ? '' : 'none';
                });
            }
            statusFilter.addEventListener('change', filterTable);
            dateFilter.addEventListener('change', filterTable);
            if (searchInput) searchInput.addEventListener('input', filterTable);
            filterTable();
        });
    </script>
</body>

</html>