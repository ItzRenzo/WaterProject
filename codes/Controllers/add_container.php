<?php
include_once '../../Database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['containerName'] ?? '';
    $capacity = $_POST['containerCapacity'] ?? '';
    $status = $_POST['containerStatus'] ?? '';

    if ($type && $capacity && $status) {
        $stmt = $conn->prepare("INSERT INTO Container (ContainerType, `ContainerCapacity(L)`, ContainerStatus) VALUES (?, ?, ?)");
        $stmt->bind_param('sds', $type, $capacity, $status);
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }
        $stmt->close();
    } else {
        echo 'error';
    }
}
