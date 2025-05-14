<?php
include_once '../../Database/db_config.php';

$where = [];
$params = [];
$types = '';

// If position is set and not 'all', filter by that position only
if (isset($_GET['position']) && $_GET['position'] && $_GET['position'] !== 'all') {
    $position = $_GET['position'];
    
    // Handle tab data-tab attributes matching
    if ($position === 'drivers') {
        $position = 'driver';
    } else if ($position === 'Cashier') {
        $position = 'cashier';
    }
    
    $where[] = 'EmployeePosition = ?';
    $params[] = $position;
    $types .= 's';
} else {
    // For 'all' tab, exclude admin
    $where[] = 'EmployeePosition != ?';
    $params[] = 'admin';
    $types .= 's';
}

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where[] = '(FirstName LIKE ? OR LastName LIKE ? OR EmployeeNumber LIKE ? OR Username LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

// Handle status filter
if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] !== 'all') {
    $status = $_GET['status'];
    $where[] = 'EmployeeStatus = ?';
    $params[] = $status;
    $types .= 's';
}

$sql = "SELECT EmployeeID, FirstName, LastName, EmployeePosition, EmployeeNumber, EmployeeStatus, Username, DateJoined FROM Employee";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY LastName, FirstName';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$employees = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
$stmt->close();
$conn->close();
echo json_encode($employees);
