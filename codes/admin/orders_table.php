<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Pagination logic
$orders_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$showAll = isset($_GET['page']) && $_GET['page'] === 'all';
$offset = ($page - 1) * $orders_per_page;

// Date filter logic
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$date_sql = '';
if ($date_filter === 'today') {
    $date_sql = "AND DATE(t.TransactionDate) = CURDATE()";
} elseif ($date_filter === 'week') {
    $date_sql = "AND YEARWEEK(t.TransactionDate, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter === 'month') {
    $date_sql = "AND YEAR(t.TransactionDate) = YEAR(CURDATE()) AND MONTH(t.TransactionDate) = MONTH(CURDATE())";
} elseif ($date_filter === 'year') {
    $date_sql = "AND YEAR(t.TransactionDate) = YEAR(CURDATE())";
}

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$search_params = [];
if ($search !== '') {
    $search_sql = " AND (t.TransactionID LIKE ? OR c.CustomerName LIKE ? OR p.ProductName LIKE ?)";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}

// Get total count for pagination with date filter and search
$count_sql = "SELECT COUNT(*) as total FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID JOIN Product p ON t.ProductID = p.ProductID WHERE 1=1 $date_sql $search_sql";
$count_stmt = $conn->prepare($count_sql);
if ($search !== '') {
    $count_stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = 0;
if ($count_result && $row = $count_result->fetch_assoc()) {
    $total_orders = $row['total'];
}
$total_pages = $orders_per_page > 0 ? ceil($total_orders / $orders_per_page) : 1;

// Retrieve paginated transactions with date filter and search
$orders = [];
if ($showAll) {
    $sql = "SELECT t.TransactionID, c.CustomerName, p.ProductName, t.Price, t.Quantity, t.PaymentMethod, t.TransactionDate FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID JOIN Product p ON t.ProductID = p.ProductID WHERE 1=1 $date_sql $search_sql ORDER BY t.TransactionDate DESC";
    $stmt = $conn->prepare($sql);
    if ($search !== '') {
        $types = str_repeat('s', count($search_params));
        $stmt->bind_param($types, ...$search_params);
    }
} else {
    $sql = "SELECT t.TransactionID, c.CustomerName, p.ProductName, t.Price, t.Quantity, t.PaymentMethod, t.TransactionDate FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID JOIN Product p ON t.ProductID = p.ProductID WHERE 1=1 $date_sql $search_sql ORDER BY t.TransactionDate DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($search !== '') {
        $types = str_repeat('s', count($search_params)) . 'ii';
        $params = array_merge($search_params, [$orders_per_page, $offset]);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $orders_per_page, $offset);
    }
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<div class="table-header">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="order-search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="table-filters">
        <select class="date-filter" id="date-filter">
            <option value="all"<?php if($date_filter==='all') echo ' selected'; ?>>All Time</option>
            <option value="today"<?php if($date_filter==='today') echo ' selected'; ?>>Today</option>
            <option value="week"<?php if($date_filter==='week') echo ' selected'; ?>>This Week</option>
            <option value="month"<?php if($date_filter==='month') echo ' selected'; ?>>This Month</option>
            <option value="year"<?php if($date_filter==='year') echo ' selected'; ?>>This Year</option>
        </select>
    </div>
    <div class="table-actions">
        <button id="select-all-orders" type="button">Select All</button>
        <button id="selected-delete" type="button">Delete Selected</button>
    </div>
</div>

<table class="orders-table">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all-checkbox"></th>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Payment Method</th>
            <th>Transaction Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><input type="checkbox" class="order-checkbox" value="<?php echo htmlspecialchars($order['TransactionID']); ?>"></td>
                    <td><?php echo htmlspecialchars($order['TransactionID']); ?></td>
                    <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                    <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                    <td>₱<?php echo number_format($order['Price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($order['Quantity']); ?></td>
                    <td><?php echo htmlspecialchars($order['PaymentMethod']); ?></td>
                    <td><?php echo htmlspecialchars($order['TransactionDate']); ?></td>
                    <td>
                        <button class="edit-order-btn" title="Edit Order" data-id="<?php echo $order['TransactionID']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-order-btn" title="Delete Order" data-id="<?php echo $order['TransactionID']; ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9">No active orders found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<!-- Show number of queries retrieved -->
<div class="orders-count" style="margin-top:10px; color:#555; font-size:15px;">
    Showing <?php echo count($orders); ?> order<?php echo count($orders) === 1 ? '' : 's'; ?> on this page.<br>
</div>
<!-- Pagination Controls -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <?php if ($page > 1): ?>
            <a href="#" class="pagination-btn" data-page="<?php echo $page - 1; ?>">&laquo;</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="#" class="pagination-btn<?php if ($i == $page) echo ' active'; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="#" class="pagination-btn" data-page="<?php echo $page + 1; ?>">&raquo;</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
