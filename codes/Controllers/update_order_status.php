<?php
// update_order_status.php
header('Content-Type: application/json');
include_once '../../Database/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

// Support both single and multiple transaction updates
$success = false;
$error = '';

if (isset($data['transaction_ids']) && is_array($data['transaction_ids']) && isset($data['status'])) {
    // Multiple transaction IDs
    $status = $data['status'];
    $placeholders = implode(',', array_fill(0, count($data['transaction_ids']), '?'));
    $types = str_repeat('i', count($data['transaction_ids']));
    $params = $data['transaction_ids'];
    $query = "UPDATE Transaction SET DeliveryStatus = ? WHERE TransactionID IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $bind_names[] = $status;
    foreach ($params as $k => $param) {
        $bind_names[] = &$params[$k];
    }
    $bind_types = 's' . $types;
    call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types], $bind_names));
    $success = $stmt->execute();
    $stmt->close();
} elseif (isset($data['transaction_id']) && isset($data['status'])) {
    // Single transaction ID
    $transaction_id = intval($data['transaction_id']);
    $status = $data['status'];
    $stmt = $conn->prepare("UPDATE Transaction SET DeliveryStatus = ? WHERE TransactionID = ?");
    $stmt->bind_param('si', $status, $transaction_id);
    $success = $stmt->execute();
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
