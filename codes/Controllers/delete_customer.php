<?php
header('Content-Type: application/json');
require_once '../../Database/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}
$id = intval($data['id']);
$stmt = $conn->prepare("UPDATE Customer SET CustomerName = CONCAT(CustomerName, '-') WHERE CustomerID = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$stmt->close();
$conn->close();