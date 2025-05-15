<?php
require_once '../../Database/db_config.php';
header('Content-Type: text/plain');

// Validate POST data
if (!isset($_POST['containerId'], $_POST['newStocks'])) {
    echo 'error';
    exit;
}

$containerId = intval($_POST['containerId']);
$newStocks = intval($_POST['newStocks']);

// Ensure stocks aren't negative
if ($newStocks < 0) {
    echo 'invalid_stock';
    exit;
}

// Get current container stocks
$currentStockQuery = $conn->prepare("SELECT Stocks FROM Container WHERE ContainerID = ? LIMIT 1");
$currentStockQuery->bind_param('i', $containerId);
$currentStockQuery->execute();
$result = $currentStockQuery->get_result();

if ($result->num_rows === 0) {
    echo 'error';
    exit;
}

$currentStock = $result->fetch_assoc()['Stocks'];

// Begin transaction
$conn->begin_transaction();
try {
    // Update container stocks
    $stmt = $conn->prepare("UPDATE Container SET Stocks=? WHERE ContainerID=?");
    $stmt->bind_param('ii', $newStocks, $containerId);
    if (!$stmt->execute()) throw new Exception('Container stock update failed');

    // Log to StockReports if stocks were added
    $stocksAdded = $newStocks - $currentStock;
    if ($stocksAdded > 0) {
        $reportStmt = $conn->prepare("INSERT INTO StockReports (ContainerID, Stocks_Added) VALUES (?, ?)");
        $reportStmt->bind_param('ii', $containerId, $stocksAdded);
        if (!$reportStmt->execute()) throw new Exception('Stock report insert failed');
    }
    
    $conn->commit();
    echo 'success';
} catch (Exception $e) {
    $conn->rollback();
    echo 'error';
}
$conn->close();
