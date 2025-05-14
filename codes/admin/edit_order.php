<?php
// admin/edit_order.php
session_start();
// Disable error display in the output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON content type before any output
header('Content-Type: application/json');

// Include database connection
include_once '../../Database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['TransactionID'])) {
    echo json_encode(['success' => false, 'error' => 'No TransactionID provided.']);
    exit;
}
$transactionId = (int)$data['TransactionID'];

// Validate and sanitize fields
$fields = ['CustomerName', 'ProductName', 'Price', 'Quantity', 'PaymentMethod', 'TransactionDate'];
foreach ($fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

// Get CustomerID and ProductID from names
$customerName = $data['CustomerName'];
$productName = $data['ProductName'];
$quantity = intval($data['Quantity']);
$paymentMethod = $data['PaymentMethod'];
$transactionDate = $data['TransactionDate'];

// Find CustomerID
$custStmt = $conn->prepare('SELECT CustomerID FROM Customer WHERE CustomerName = ? LIMIT 1');
if (!$custStmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$custStmt->bind_param('s', $customerName);
if (!$custStmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to execute customer query: ' . $custStmt->error]);
    exit;
}
$custRes = $custStmt->get_result();
if (!$custRes || !$custRes->num_rows) {
    echo json_encode(['success' => false, 'error' => 'Customer not found.']);
    exit;
}
$customerId = $custRes->fetch_assoc()['CustomerID'];

// Get the old quantity for this transaction
$oldQtyStmt = $conn->prepare('SELECT Quantity FROM Transaction WHERE TransactionID = ? LIMIT 1');
$oldQtyStmt->bind_param('i', $transactionId);
$oldQtyStmt->execute();
$oldQtyRes = $oldQtyStmt->get_result();
if (!$oldQtyRes || !$oldQtyRes->num_rows) {
    echo json_encode(['success' => false, 'error' => 'Order not found.']);
    exit;
}
$oldQuantity = intval($oldQtyRes->fetch_assoc()['Quantity']);

// Find ProductID, ProductPrice, and Stocks
$prodStmt = $conn->prepare('SELECT ProductID, ProductPrice, Stocks FROM Product WHERE ProductName = ? LIMIT 1');
if (!$prodStmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$prodStmt->bind_param('s', $productName);
if (!$prodStmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to execute product query: ' . $prodStmt->error]);
    exit;
}
$prodRes = $prodStmt->get_result();
if (!$prodRes || !$prodRes->num_rows) {
    echo json_encode(['success' => false, 'error' => 'Product not found.']);
    exit;
}
$product = $prodRes->fetch_assoc();
$productId = $product['ProductID'];
$currentStock = intval($product['Stocks']);
$unitPrice = floatval($product['ProductPrice']);
$price = $unitPrice * $quantity;

// Calculate the stock adjustment
$qtyDiff = $quantity - $oldQuantity;

// If increasing quantity, check if enough stock
if ($qtyDiff > 0 && $qtyDiff > $currentStock) {
    echo json_encode([
        'success' => false,
        'error' => 'Not enough stock available. You can only add up to ' . $currentStock . ' more units.',
        'stocks' => $currentStock
    ]);
    exit;
}

// Update stocks only if quantity changed
$newStock = $currentStock - $qtyDiff;
if ($qtyDiff != 0) {
    $updateStockStmt = $conn->prepare('UPDATE Product SET Stocks = ? WHERE ProductID = ?');
    $updateStockStmt->bind_param('ii', $newStock, $productId);
    if (!$updateStockStmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to update product stock.']);
        exit;
    }
}

// Update Transaction
$updateStmt = $conn->prepare('UPDATE Transaction SET CustomerID=?, ProductID=?, Price=?, Quantity=?, PaymentMethod=?, TransactionDate=? WHERE TransactionID=?');
if (!$updateStmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$updateStmt->bind_param('iidissi', $customerId, $productId, $price, $quantity, $paymentMethod, $transactionDate, $transactionId);
if ($updateStmt->execute()) {
    echo json_encode([
        'success' => true,
        'updatedData' => [
            'price' => $price,
            'unitPrice' => $unitPrice,
            'quantity' => $quantity,
            'stocks' => $newStock
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $updateStmt->error]);
}
