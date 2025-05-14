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

$id = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$date = trim($data['date'] ?? '');
$paymentMethod = trim($data['paymentMethod'] ?? ''); // Not used in DB
$description = trim($data['description'] ?? '');

if (!$id || !$name || !$category || !$amount || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Get the ExpenseTypeID for this expense
$sql = "SELECT ExpenseTypeID FROM LoggedExpenses WHERE ExpensesID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($typeId);
if ($stmt->fetch()) {
    $stmt->close();
    // Update ExpenseTypes
    $sql = "UPDATE ExpenseTypes SET TypeName = ?, Category = ? WHERE ExpenseTypeID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $name, $category, $typeId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Expense not found.']);
    $conn->close();
    exit;
}
// Update LoggedExpenses
$sql = "UPDATE LoggedExpenses SET Amount = ?, ExpenseDate = ?, Description = ? WHERE ExpensesID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('dssi', $amount, $date, $description, $id);
$success = $stmt->execute();
$stmt->close();
$conn->close();
echo json_encode(['success' => $success]);
