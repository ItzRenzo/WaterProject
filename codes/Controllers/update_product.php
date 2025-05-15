<?php
require_once '../../Database/db_config.php';
header('Content-Type: text/plain');

// Validate POST data
if (!isset($_POST['oldProductName'], $_POST['productName'], $_POST['productPrice'], $_POST['productStatus'], $_POST['productContainer'])) {
    echo 'error';
    exit;
}

$oldProductName = trim($_POST['oldProductName']);
$productName = trim($_POST['productName']);
$productPrice = floatval($_POST['productPrice']);
$productDescription = isset($_POST['productDescription']) ? trim($_POST['productDescription']) : '';
$productStatus = trim($_POST['productStatus']);
$productContainer = intval($_POST['productContainer']);
$addStocks = isset($_POST['addStocks']) ? intval($_POST['addStocks']) : 0;

// Map status to DB value
$dbStatus = ($productStatus === 'active') ? 'Available' : 'Inactive';

// Get ProductID and current stocks
$productRes = $conn->prepare("SELECT ProductID, Stocks FROM Product WHERE ProductName=? LIMIT 1");
$productRes->bind_param('s', $oldProductName);
$productRes->execute();
$productRow = $productRes->get_result()->fetch_assoc();
if (!$productRow) {
    echo 'error';
    exit;
}
$productId = $productRow['ProductID'];
$currentProductStocks = intval($productRow['Stocks']);

// Get ContainerID and current stocks
$containerRes = $conn->prepare("SELECT ContainerID, Stocks FROM Container WHERE ContainerID=? LIMIT 1");
$containerRes->bind_param('i', $productContainer);
$containerRes->execute();
$containerRow = $containerRes->get_result()->fetch_assoc();
if (!$containerRow) {
    echo 'error';
    exit;
}
$containerId = $containerRow['ContainerID'];
$currentContainerStocks = intval($containerRow['Stocks']);

// Begin transaction
$conn->begin_transaction();
try {
    // Update product info
    $stmt = $conn->prepare("UPDATE Product SET ProductName=?, ProductPrice=?, ProductDescription=?, ProductStatus=?, ContainerID=? WHERE ProductID=?");
    $stmt->bind_param('sdssii', $productName, $productPrice, $productDescription, $dbStatus, $productContainer, $productId);
    if (!$stmt->execute()) throw new Exception('Product update failed');

    // If stocks are being added, update stocks
    if ($addStocks > 0) {
        // Check container has enough stocks
        if ($currentContainerStocks < $addStocks) {
            $conn->rollback();
            echo 'not_enough_container_stocks';
            $conn->close();
            exit;
        }
        // Update product stocks
        $newProductStocks = $currentProductStocks + $addStocks;
        $stmt2 = $conn->prepare("UPDATE Product SET Stocks=? WHERE ProductID=?");
        $stmt2->bind_param('ii', $newProductStocks, $productId);
        if (!$stmt2->execute()) throw new Exception('Product stock update failed');
        // Update container stocks
        $newContainerStocks = $currentContainerStocks - $addStocks;
        $stmt3 = $conn->prepare("UPDATE Container SET Stocks=? WHERE ContainerID=?");
        $stmt3->bind_param('ii', $newContainerStocks, $containerId);
        if (!$stmt3->execute()) throw new Exception('Container stock update failed');
    }
    $conn->commit();
    echo 'success';
} catch (Exception $e) {
    $conn->rollback();
    echo 'error';
}
$conn->close();
