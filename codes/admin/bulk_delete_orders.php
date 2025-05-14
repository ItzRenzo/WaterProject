<?php
// admin/bulk_delete_orders.php
session_start();
include_once '../../Database/db_config.php';
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ids']) || !is_array($data['ids']) || count($data['ids']) === 0) {
    echo json_encode(['success' => false, 'error' => 'No IDs provided.']);
    exit;
}

$ids = $data['ids'];
// TransactionID is INT in your database, so validate as integer
foreach ($ids as $id) {
    if (!ctype_digit((string)$id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID detected.']);
        exit;
    }
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids)); // Use 'i' for integer

$sql = "UPDATE Transaction SET Status = 'delete' WHERE TransactionID IN ($placeholders)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$ids);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'affected' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}
