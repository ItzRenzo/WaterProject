<?php
// update_order_status.php
header('Content-Type: application/json');
include_once '../../Database/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

// Support both single and multiple transaction updates
$success = false;
$error = '';

// Convert status to lowercase for consistency
if (isset($data['status'])) {
    $data['status'] = strtolower($data['status']);
}

if (isset($data['transaction_ids']) && is_array($data['transaction_ids']) && isset($data['status'])) {
    // Multiple transaction IDs
    $status = $data['status'];
    $placeholders = implode(',', array_fill(0, count($data['transaction_ids']), '?'));
    $types = str_repeat('i', count($data['transaction_ids']));
    $params = $data['transaction_ids'];
    $query = "UPDATE Transaction SET DeliveryStatus = ? WHERE TransactionID IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $bind_types = 's' . $types;
    $bind_values = [$bind_types, &$status];
    foreach ($params as $k => &$param) {
        $bind_values[] = &$param;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_values);
    $success = $stmt->execute();
    $stmt->close();
} elseif (isset($data['transaction_id']) && isset($data['status'])) {
    // Single transaction ID
    $transaction_id = intval($data['transaction_id']);
    $status = $data['status'];
    $success = false;
    // Update DeliveryStatus
    $stmt = $conn->prepare("UPDATE Transaction SET DeliveryStatus = ? WHERE TransactionID = ?");
    $stmt->bind_param('si', $status, $transaction_id);
    $success = $stmt->execute();
    $stmt->close();
    // If address is provided, update CustomerAddress for this transaction's customer
    if ($success && isset($data['address'])) {
        // Get CustomerID for this transaction
        $stmt = $conn->prepare("SELECT CustomerID FROM Transaction WHERE TransactionID = ?");
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        $stmt->bind_result($customer_id);
        if ($stmt->fetch() && $customer_id) {
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE Customer SET CustomerAddress = ? WHERE CustomerID = ?");
            $stmt2->bind_param('si', $data['address'], $customer_id);
            $success = $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt->close();
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
