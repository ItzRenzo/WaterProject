<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received.']);
    exit;
}

$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$date = trim($data['date'] ?? '');
$paymentMethod = trim($data['paymentMethod'] ?? ''); // Not used in DB
$description = trim($data['description'] ?? '');

if (!$name || !$category || !$amount || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Find or create ExpenseTypeID
$sql = "SELECT ExpenseTypeID FROM ExpenseTypes WHERE TypeName = ? AND Category = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $name, $category);
$stmt->execute();
$stmt->bind_result($typeId);
if ($stmt->fetch()) {
    $stmt->close();
} else {
    $stmt->close();
    $sql = "INSERT INTO ExpenseTypes (TypeName, Category) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $name, $category);
    $stmt->execute();
    $typeId = $stmt->insert_id;
    $stmt->close();
}

// Insert into LoggedExpenses
$sql = "INSERT INTO LoggedExpenses (ExpenseTypeID, Amount, ExpenseDate, Description) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('idss', $typeId, $amount, $date, $description);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
