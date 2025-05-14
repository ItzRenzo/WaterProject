<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'No expense ID provided.']);
    exit;
}

$expenseId = intval($_POST['id']);

// Get the ExpenseTypeID for this expense
$sql = "SELECT ExpenseTypeID FROM LoggedExpenses WHERE ExpensesID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $expenseId);
$stmt->execute();
$stmt->bind_result($typeId);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Expense not found.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Update the TypeName to append (delete)
$sql = "UPDATE ExpenseTypes SET TypeName = CONCAT(TypeName, ' (delete)') WHERE ExpenseTypeID = ? AND TypeName NOT LIKE '% (delete)'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $typeId);
$stmt->execute();
$success = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
