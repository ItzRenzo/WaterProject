<?php
include_once '../../Database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['productName'] ?? '';
    $price = $_POST['productPrice'] ?? '';
    $description = $_POST['productDescription'] ?? '';
    $status = $_POST['productStatus'] === 'active' ? 'Available' : 'Inactive';
    $containerId = $_POST['productContainer'] ?? null;
    $initialStocks = $_POST['initialStocks'] ?? 0;
    $initialStocks = intval($initialStocks);

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // If initial stocks are specified and container is selected
        if ($initialStocks > 0 && $containerId) {
            // Check container stocks
            $containerQuery = $conn->prepare("SELECT Stocks FROM Container WHERE ContainerID = ?");
            $containerQuery->bind_param('i', $containerId);
            $containerQuery->execute();
            $containerResult = $containerQuery->get_result();
            
            if ($containerResult->num_rows === 0) {
                throw new Exception("Container not found");
            }
            
            $containerStocks = $containerResult->fetch_assoc()['Stocks'];
            
            // Check if enough container stocks are available
            if ($containerStocks < $initialStocks) {
                $conn->rollback();
                echo 'not_enough_container_stocks';
                exit;
            }
            
            // Update container stocks
            $newContainerStocks = $containerStocks - $initialStocks;
            $updateContainer = $conn->prepare("UPDATE Container SET Stocks = ? WHERE ContainerID = ?");
            $updateContainer->bind_param('ii', $newContainerStocks, $containerId);
            if (!$updateContainer->execute()) {
                throw new Exception("Failed to update container stocks");
            }
        }
        
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO Product (ProductName, ProductPrice, ProductDescription, ProductStatus, ContainerID, Stocks) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sdssii', $name, $price, $description, $status, $containerId, $initialStocks);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo 'success';
        } else {
            throw new Exception("Failed to insert product");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo 'error';
    }
    
    $conn->close();
} else {
    echo 'error';
}
