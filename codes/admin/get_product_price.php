<?php
// get_product_price.php
session_start();
// Disable error display in the output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON content type before any output
header('Content-Type: application/json');

// Include database connection
include_once '../../Database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_GET['productName'])) {
    echo json_encode(['success' => false, 'error' => 'No product name provided']);
    exit;
}

$productName = $_GET['productName'];

// Find product and get price and stock
$stmt = $conn->prepare('SELECT ProductID, ProductPrice, Stocks FROM Product WHERE ProductName = ? LIMIT 1');
$stmt->bind_param('s', $productName);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !$result->num_rows) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();
echo json_encode(['success' => true, 'product' => $product]);
?>
