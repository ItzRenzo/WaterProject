<?php
require_once '../../Database/db_config.php';

// Date range filter
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

$where = "WHERE DATE(TransactionDate) >= '$startDate' AND DATE(TransactionDate) <= '$endDate'";
$where_exp = "WHERE ExpenseDate >= '$startDate' AND ExpenseDate <= '$endDate'";

// Total Sales (only Delivered and Completed)
$totalSales = 0;
$res = $conn->query("SELECT SUM(Price) as total FROM Transaction $where AND (DeliveryStatus = 'Delivered' OR DeliveryStatus = 'Completed')");
if ($res && $row = $res->fetch_assoc()) $totalSales = (float)$row['total'];

// Orders
$totalOrders = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM Transaction $where");
if ($res && $row = $res->fetch_assoc()) $totalOrders = (int)$row['total'];

// Customers
$totalCustomers = 0;
$res = $conn->query("SELECT COUNT(DISTINCT CustomerID) as total FROM Transaction $where");
if ($res && $row = $res->fetch_assoc()) $totalCustomers = (int)$row['total'];

// Expenses
$totalExpenses = 0;
$res = $conn->query("SELECT SUM(Amount) as total FROM LoggedExpenses $where_exp");
if ($res && $row = $res->fetch_assoc()) $totalExpenses = (float)$row['total'];

// Recent Transactions
$recentTransactions = [];
$res = $conn->query("SELECT t.TransactionID, t.TransactionDate, c.CustomerName, t.Quantity, t.Price, t.DeliveryStatus FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where ORDER BY t.TransactionDate DESC LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recentTransactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/reports.css">
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
        <div class="reports-header">
            <h2>Reports</h2>
            <div class="report-actions">
                <div class="date-range">
                    <div class="date-input">
                        <label for="startDate">From:</label>
                        <input type="date" id="startDate" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="date-input">
                        <label for="endDate">To:</label>
                        <input type="date" id="endDate" value="<?php echo $endDate; ?>">
                    </div>
                </div>
                <button class="export-btn">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>

        <!-- Report Overview Cards -->
        <div class="report-overview">
            <div class="report-card">
                <div class="report-card-icon sales">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="report-card-info">
                    <h3>Total Sales</h3>
                    <p class="amount">₱<?php echo number_format($totalSales, 2); ?></p>
                </div>
            </div>
            <div class="report-card">
                <div class="report-card-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="report-card-info">
                    <h3>Orders</h3>
                    <p class="amount"><?php echo $totalOrders; ?></p>
                </div>
            </div>
            <div class="report-card">
                <div class="report-card-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="report-card-info">
                    <h3>Customers</h3>
                    <p class="amount"><?php echo $totalCustomers; ?></p>
                </div>
            </div>
            <div class="report-card">
                <div class="report-card-icon expenses">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="report-card-info">
                    <h3>Expenses</h3>
                    <p class="amount">₱<?php echo number_format($totalExpenses, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="report-section">
            <div class="section-header">
                <h3>Recent Transactions</h3>
                <div class="section-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td>#ORD<?php echo $tx['TransactionID']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($tx['TransactionDate'])); ?></td>
                            <td><?php echo htmlspecialchars($tx['CustomerName']); ?></td>
                            <td><?php echo $tx['Quantity']; ?> item<?php echo $tx['Quantity'] > 1 ? 's' : ''; ?></td>
                            <td>₱<?php echo number_format($tx['Price'], 2); ?></td>
                            <td><span class="status-badge <?php echo strtolower($tx['DeliveryStatus']) === 'pending' ? 'pending' : 'complete'; ?>"><?php echo ucfirst($tx['DeliveryStatus']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <button class="page-btn"><i class="fas fa-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Export Options Modal -->
    <div class="modal" id="exportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Report</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="exportFormat">Format</label>
                    <select id="exportFormat">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button class="cancel-btn">Cancel</button>
                    <button class="export-now-btn"><i class="fas fa-file-export"></i> Export</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Set default dates
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

        document.getElementById('startDate').valueAsDate = firstDay;
        document.getElementById('endDate').valueAsDate = today;

        // Export modal functionality
        const exportBtn = document.querySelector('.export-btn');
        const exportModal = document.getElementById('exportModal');
        const closeBtn = document.querySelector('.close-btn');
        const cancelBtn = document.querySelector('.cancel-btn');

        exportBtn.addEventListener('click', function () {
            exportModal.classList.add('show');
        });

        closeBtn.addEventListener('click', function () {
            exportModal.classList.remove('show');
        });

        cancelBtn.addEventListener('click', function () {
            exportModal.classList.remove('show');
        });

        // Close modal on outside click
        window.addEventListener('click', function (e) {
            if (e.target === exportModal) {
                exportModal.classList.remove('show');
            }
        });
    </script>
</body>

</html>