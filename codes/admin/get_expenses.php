<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

$where = [];
$params = [];
$types = '';

if (isset($_GET['from']) && $_GET['from']) {
    $where[] = 'le.ExpenseDate >= ?';
    $params[] = $_GET['from'];
    $types .= 's';
}
if (isset($_GET['to']) && $_GET['to']) {
    $where[] = 'le.ExpenseDate <= ?';
    $params[] = $_GET['to'];
    $types .= 's';
}
if (isset($_GET['search']) && $_GET['search']) {
    $where[] = '(et.TypeName LIKE ? OR et.Category LIKE ? OR le.Description LIKE ?)';
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
    $types .= 'sss';
}

$sql = "SELECT le.ExpensesID, et.TypeName, et.Category, le.Amount, le.ExpenseDate, le.Description
        FROM LoggedExpenses le
        JOIN ExpenseTypes et ON le.ExpenseTypeID = et.ExpenseTypeID";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY le.ExpenseDate DESC, le.ExpensesID DESC';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$expenses = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
}
$stmt->close();
$conn->close();
echo json_encode($expenses);
