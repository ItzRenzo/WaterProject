<?php
require_once '../../Database/db_config.php';
require_once __DIR__ . '/../../vendor/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Handle export
if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'excel', 'pdf'])) {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_sql = '';
    $search_params = [];
    if ($search !== '') {
        $search_sql = " AND (t.TransactionID LIKE ? OR c.CustomerName LIKE ? OR t.DeliveryStatus LIKE ?)";
        $search_params = ["%$search%", "%$search%", "%$search%"];
    }
    $where = "WHERE DATE(TransactionDate) >= '$startDate' AND DATE(TransactionDate) <= '$endDate' AND (DeliveryStatus = 'Delivered' OR DeliveryStatus = 'Completed')" . $search_sql;
    $query = "SELECT t.TransactionID, t.TransactionDate, c.CustomerName, t.Quantity, t.Price, t.DeliveryStatus FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where ORDER BY t.TransactionDate DESC";
    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    $totalSales = 0;
    $totalOrders = 0;
    $totalItems = 0;
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
            $totalSales += (float)$row['Price'];
            $totalOrders++;
            $totalItems += (int)$row['Quantity'];
        }
    }
    if ($_GET['export'] === 'csv' || $_GET['export'] === 'excel') {
        $filename = 'sales_report_' . $startDate . '_to_' . $endDate . '.' . ($_GET['export'] === 'csv' ? 'csv' : 'xls');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID', 'Date', 'Customer', 'Quantity', 'Amount', 'Status']);
        foreach ($rows as $row) {
            fputcsv($out, [
                '#ORD' . $row['TransactionID'],
                date('Y-m-d H:i', strtotime($row['TransactionDate'])),
                $row['CustomerName'],
                $row['Quantity'],
                $row['Price'],
                $row['DeliveryStatus']
            ]);
        }
        fclose($out);
        exit;
    } elseif ($_GET['export'] === 'pdf') {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sales Report</title>';
        $html .= '<style>body{font-family:sans-serif;font-size:12px;}h2{margin-bottom:0;}table{border-collapse:collapse;width:100%;margin-top:10px;}th,td{border:1px solid #333;padding:4px 6px;text-align:left;}th{background:#eee;}tfoot td{font-weight:bold;}</style>';
        $html .= '</head><body>';
        $html .= '<h2>Sales Report</h2>';
        $html .= '<div>Date Range: <b>' . htmlspecialchars($startDate) . '</b> to <b>' . htmlspecialchars($endDate) . '</b></div>';
        $html .= '<div>Total Orders: <b>' . $totalOrders . '</b> | Total Items: <b>' . $totalItems . '</b> | Total Sales: <b>₱' . number_format($totalSales,2) . '</b></div>';
        $html .= '<table><thead><tr><th>Order ID</th><th>Date</th><th>Customer</th><th>Items</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td>#ORD' . $row['TransactionID'] . '</td>';
            $html .= '<td>' . date('Y-m-d H:i', strtotime($row['TransactionDate'])) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['CustomerName']) . '</td>';
            $html .= '<td>' . $row['Quantity'] . '</td>';
            $html .= '<td>₱' . number_format($row['Price'],2) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['DeliveryStatus']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('sales_report.pdf', ['Attachment' => false]);
        exit;
    }
}

// Date range filter
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$search_params = [];
if ($search !== '') {
    $search_sql = " AND (t.TransactionID LIKE ? OR c.CustomerName LIKE ? OR t.DeliveryStatus LIKE ?)";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}
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

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) && (is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$showAll = isset($_GET['page']) && $_GET['page'] === 'all';
$offset = ($page - 1) * $perPage;

// Count total filtered transactions
if ($search !== '') {
    $countQuery = "SELECT COUNT(*) as total FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where $search_sql";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
} else {
    $countRes = $conn->query("SELECT COUNT(*) as total FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where");
}
$totalRows = 0;
if ($countRes && $row = $countRes->fetch_assoc()) $totalRows = (int)$row['total'];
$totalPages = max(1, ceil($totalRows / $perPage));

// Recent Transactions (paginated or all)
$recentTransactions = [];
if ($showAll) {
    $query = "SELECT t.TransactionID, t.TransactionDate, c.CustomerName, t.Quantity, t.Price, t.DeliveryStatus FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where $search_sql ORDER BY t.TransactionDate DESC";
    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $query = "SELECT t.TransactionID, t.TransactionDate, c.CustomerName, t.Quantity, t.Price, t.DeliveryStatus FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID $where $search_sql ORDER BY t.TransactionDate DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $types = str_repeat('s', count($search_params)) . 'ii';
        $params = array_merge($search_params, [$perPage, $offset]);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute();
    $res = $stmt->get_result();
}
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
    <style>
    /* Removed all @media print CSS as requested */
    </style>
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
            <form id="date-filter-form" method="get" style="display:inline;">
                <div class="report-actions">
                    <div class="date-range">
                        <div class="date-input">
                            <label for="startDate">From:</label>
                            <input type="date" id="startDate" name="startDate" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="date-input">
                            <label for="endDate">To:</label>
                            <input type="date" id="endDate" name="endDate" value="<?php echo $endDate; ?>">
                        </div>
                    </div>
                    <button type="button" class="export-btn" id="export-btn">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>
            </form>
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
                        <form method="get" id="search-form" style="display:inline;">
                            <input type="hidden" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                            <input type="hidden" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
                            <input type="text" name="search" id="search-input" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                        </form>
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
                <?php if (!$showAll): ?>
                    <?php if ($page > 1): ?>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="page-btn<?php if ($i == $page) echo ' active'; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 'all'])); ?>">All</a>
                <?php else: ?>
                    <a class="page-btn active" href="#">All</a>
                    <a class="page-btn" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">Paginate</a>
                <?php endif; ?>
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

        // Date filter auto-submit
        document.getElementById('startDate').addEventListener('change', function() {
            document.getElementById('date-filter-form').submit();
        });
        document.getElementById('endDate').addEventListener('change', function() {
            document.getElementById('date-filter-form').submit();
        });

        // Export modal functionality
        const exportBtn = document.getElementById('export-btn');
        exportBtn.addEventListener('click', function () {
            const format = prompt('Export as: csv, excel, or pdf? (Type one)');
            if (!format) return;
            if (!['csv','excel','pdf'].includes(format)) { alert('Invalid format.'); return; }
            const form = document.getElementById('date-filter-form');
            const params = new URLSearchParams(new FormData(form));
            params.set('export', format);
            window.location = 'reports.php?' + params.toString();
        });

        const closeBtn = document.querySelector('.close-btn');
        const cancelBtn = document.querySelector('.cancel-btn');

        closeBtn.addEventListener('click', function () {
            document.getElementById('exportModal').classList.remove('show');
        });

        cancelBtn.addEventListener('click', function () {
            document.getElementById('exportModal').classList.remove('show');
        });

        // Close modal on outside click
        window.addEventListener('click', function (e) {
            if (e.target === document.getElementById('exportModal')) {
                document.getElementById('exportModal').classList.remove('show');
            }
        });

        // Submit search on enter
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('search-form').submit();
                }
            });
        }
    </script>
</body>

</html>