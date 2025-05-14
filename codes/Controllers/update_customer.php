<?php
header('Content-Type: application/json');
require_once '../../Database/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'], $data['name'], $data['phone'], $data['address'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$id = intval($data['id']);
$name = trim($data['name']);
$phone = trim($data['phone']);
$address = trim($data['address']);

if (strlen($name) < 2 || strlen($address) < 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid name or address.']);
    exit;
}

$phoneRegex = '/^09\d{9}$/';
if (!preg_match($phoneRegex, $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number must start with 09 and be 11 digits.']);
    exit;
}

$stmt = $conn->prepare("UPDATE Customer SET CustomerName=?, CustomerNumber=?, CustomerAddress=? WHERE CustomerID=?");
$stmt->bind_param('sssi', $name, $phone, $address, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$stmt->close();
$conn->close();
