<?php
require_once '../../Database/db_config.php';
header('Content-Type: text/plain');

if (!isset($_POST['productId'])) {
    echo 'error';
    exit;
}

$productId = intval($_POST['productId']);

$stmt = $conn->prepare("UPDATE Product SET ProductStatus='deleted' WHERE ProductID=?");
$stmt->bind_param('i', $productId);
if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}
$conn->close();
