<?php
require_once '../../Database/db_config.php';
header('Content-Type: text/plain');

if (!isset($_POST['containerId'])) {
    echo 'error';
    exit;
}

$containerId = intval($_POST['containerId']);

$stmt = $conn->prepare("UPDATE Container SET ContainerStatus='deleted' WHERE ContainerID=?");
$stmt->bind_param('i', $containerId);
if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}
$conn->close();
