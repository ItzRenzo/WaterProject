<?php
// update_product_stock.php
require_once '../../Database/database.php';
header('Content-Type: application/json');

// Validate POST data
if (!isset($_POST['productName'], $_POST['addStocks'], $_POST['containerType'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

$productName = trim($_POST['productName']);
$addStocks = intval($_POST['addStocks']);
$containerType = trim($_POST['containerType']);

if ($addStocks <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid stock amount.']);
    exit;
}

// Get product info
$productQuery = $conn->prepare("SELECT ProductID, Stocks, ContainerID FROM Product WHERE ProductName = ? LIMIT 1");
$productQuery->bind_param('s', $productName);
$productQuery->execute();
$productResult = $productQuery->get_result();
if ($productResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found.']);
    exit;
}
$product = $productResult->fetch_assoc();
$productId = $product['ProductID'];
$currentProductStock = intval($product['Stocks']);
$containerId = $product['ContainerID'];

// Get container info by type (ensure correct container)
$containerQuery = $conn->prepare("SELECT ContainerID, Stocks FROM Container WHERE ContainerType = ? LIMIT 1");
$containerQuery->bind_param('s', $containerType);
$containerQuery->execute();
$containerResult = $containerQuery->get_result();
if ($containerResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Container not found.']);
    exit;
}
$container = $containerResult->fetch_assoc();
$containerStocks = intval($container['Stocks']);
$containerIdFromType = $container['ContainerID'];

// Check if container has enough stocks
if ($containerStocks < $addStocks) {
    echo json_encode(['success' => false, 'error' => 'Not enough container stocks.']);
    exit;
}

// Begin transaction
$conn->begin_transaction();
try {
    // Update product stocks
    $newProductStock = $currentProductStock + $addStocks;
    $updateProduct = $conn->prepare("UPDATE Product SET Stocks = ? WHERE ProductID = ?");
    $updateProduct->bind_param('ii', $newProductStock, $productId);
    if (!$updateProduct->execute()) throw new Exception('Failed to update product stocks.');

    // Subtract from container stocks
    $newContainerStock = $containerStocks - $addStocks;
    $updateContainer = $conn->prepare("UPDATE Container SET Stocks = ? WHERE ContainerID = ?");
    $updateContainer->bind_param('ii', $newContainerStock, $containerIdFromType);
    if (!$updateContainer->execute()) throw new Exception('Failed to update container stocks.');

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
