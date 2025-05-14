<?php
header('Content-Type: application/json');
require_once '../../Database/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name'], $data['phone'], $data['address'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$name = trim($data['name']);
$phone = trim($data['phone']);
$address = trim($data['address']);

if (strlen($name) < 2 || strlen($address) < 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid name or address.']);
    exit;
}

$phoneRegex = '/^09\d{9}$/';
if (!preg_match($phoneRegex, $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO Customer (CustomerName, CustomerNumber, CustomerAddress) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $name, $phone, $address);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$stmt->close();
$conn->close();
