<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - RJane Water Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Css/order.css">
  
</head>
<body>
<?php
// Database connection
require_once('../../Database/db_config.php');

// Get the latest transaction based on date and time
// Group transactions by order (assuming same TransactionDate = same order)
$latest_order_query = "SELECT MAX(TransactionDate) as LatestDate FROM Transaction";
$latest_date_result = $conn->query($latest_order_query);

if (!$latest_date_result) {
    die("Query failed: " . $conn->error);
}

$latest_date = null;
if ($latest_date_result->num_rows > 0) {
    $row = $latest_date_result->fetch_assoc();
    $latest_date = $row['LatestDate'];
}

// Now get all products from the latest transaction date
$query = "SELECT t.*, c.CustomerName, c.CustomerNumber, c.CustomerAddress, p.ProductName, p.ProductPrice 
          FROM Transaction t 
          JOIN Customer c ON t.CustomerID = c.CustomerID
          JOIN Product p ON p.ProductID = t.ProductID
          WHERE t.TransactionDate = '$latest_date'
          ORDER BY t.TransactionID DESC";
          
$result = $conn->query($query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Prepare an array to store transactions
$transactions = [];
$customerInfo = [];

// Process the result
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Store transaction details
        $transactions[] = [
            'TransactionID' => $row['TransactionID'],
            'ProductName' => $row['ProductName'],
            'Price' => $row['Price'],
            'Quantity' => isset($row['Quantity']) ? (int)$row['Quantity'] : 1,
            'ProductPrice' => isset($row['ProductPrice']) ? $row['ProductPrice'] : $row['Price'],
            'PaymentMethod' => $row['PaymentMethod'],
            'DeliveryMethod' => $row['DeliveryMethod'],
            'DeliveryStatus' => $row['DeliveryStatus']
        ];
        // Store customer info (only need to do this once)
        if (empty($customerInfo)) {
            $customerInfo = [
                'CustomerName' => $row['CustomerName'],
                'CustomerNumber' => $row['CustomerNumber'],
                'CustomerAddress' => $row['CustomerAddress'],
                'TransactionDate' => $row['TransactionDate'],
                'PaymentMethod' => $row['PaymentMethod'],
                'DeliveryMethod' => $row['DeliveryMethod'],
                'DeliveryStatus' => $row['DeliveryStatus']
            ];
        }
    }
}
?>    <div class="receipt-container">
        <div class="receipt-header">
            <h3>RJANE WATER STATION</h3>
            <?php if (!empty($transactions)): ?>
                <p id="order-number">Order #: RJ<?php echo $transactions[0]['TransactionID']; ?></p>
                <p id="order-date">Date: <?php echo date('M d, Y h:i A', strtotime($latest_date)); ?></p>
            <?php else: ?>
                <p id="order-number">Order #: N/A</p>
                <p id="order-date">Date: <?php echo date('M d, Y h:i A'); ?></p>
            <?php endif; ?>
        </div>
          <div class="receipt-customer-details">
            <p><strong>Customer:</strong> <span id="customer-name"><?php echo !empty($customerInfo) ? htmlspecialchars($customerInfo['CustomerName']) : 'N/A'; ?></span></p>
            <p><strong>Phone:</strong> <span id="customer-phone"><?php echo !empty($customerInfo) ? htmlspecialchars($customerInfo['CustomerNumber']) : 'N/A'; ?></span></p>
            <p><strong>Payment Method:</strong> <span id="payment-method"><?php echo !empty($customerInfo) ? htmlspecialchars($customerInfo['PaymentMethod']) : 'N/A'; ?></span></p>
            <p><strong>Delivery:</strong> <span id="delivery-method"><?php echo !empty($customerInfo) ? htmlspecialchars($customerInfo['DeliveryMethod']) : 'N/A'; ?></span></p>
            <p><strong>Delivery Status:</strong> <span id="delivery-status"><?php echo !empty($customerInfo) && isset($customerInfo['DeliveryStatus']) ? htmlspecialchars($customerInfo['DeliveryStatus']) : (isset($transactions[0]['DeliveryStatus']) ? htmlspecialchars($transactions[0]['DeliveryStatus']) : 'N/A'); ?></span></p>
            <p id="address-row"><strong>Address:</strong> <span id="customer-address"><?php 
                if (!empty($customerInfo) && 
                    (strtolower($customerInfo['DeliveryMethod']) == 'home delivery' || strtolower($customerInfo['DeliveryMethod']) == 'delivery')) {
                    echo htmlspecialchars($customerInfo['CustomerAddress']);
                } else {
                    echo 'N/A';
                }
            ?></span></p>
        </div>
          <h4>Items:</h4>
        <table class="receipt-items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="receipt-items">
                <?php
                if (!empty($transactions)) {
                    $totalAmount = 0;
                    foreach ($transactions as $transaction) {
                        $quantity = isset($transaction['Quantity']) ? (int)$transaction['Quantity'] : 1;
                        $unitPrice = isset($transaction['ProductPrice']) ? $transaction['ProductPrice'] : ($transaction['Price'] / $quantity);
                        $lineTotal = $unitPrice * $quantity;
                        $totalAmount += $lineTotal;
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($transaction['ProductName']) . '</td>';
                        echo '<td>' . $quantity . '</td>';
                        echo '<td>₱' . number_format($unitPrice, 2) . '</td>';
                        echo '<td>₱' . number_format($lineTotal, 2) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4" style="text-align: center;">No items found</td></tr>';
                }
                ?>
            </tbody>
        </table>
          <div class="receipt-total">
            <p>TOTAL: <span id="receipt-total">
                <?php
                if (!empty($transactions)) {
                    $totalAmount = 0;
                    foreach ($transactions as $transaction) {
                        $totalAmount += $transaction['Price'];
                    }
                    echo '₱' . number_format($totalAmount, 2);
                } else {
                    echo '₱0.00';
                }
                ?>
            </span></p>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
        </div>
        
        <div class="receipt-actions">
            <button id="print-receipt" onclick="window.print()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            
            <button id="back" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Order
            </button>
        </div>
    </div>    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Single event listener for back button
            document.getElementById('back').addEventListener('click', function() {
                window.location.href = 'order.php';
            });
            
            // Only try to parse order data from URL if we don't have PHP data
            <?php if (empty($transactions)): ?>
            try {
                const params = new URLSearchParams(window.location.search);
                const orderDataParam = params.get('orderData');
                
                if (orderDataParam) {
                    const orderData = JSON.parse(decodeURIComponent(orderDataParam));
                    
                    // Populate receipt with order data
                    document.getElementById('order-number').textContent = 'Order #: ' + (orderData.orderNumber || 'RJ123456');
                    document.getElementById('order-date').textContent = 'Date: ' + (orderData.orderDate || new Date().toLocaleString());
                    
                    // Set customer name with proper fallback
                    document.getElementById('customer-name').textContent = orderData.customerName || 'N/A';
                    
                    // Set phone number
                    document.getElementById('customer-phone').textContent = orderData.customerPhone || 'N/A';
                    
                    // Set payment method
                    document.getElementById('payment-method').textContent = getPaymentMethodText(orderData.paymentMethod) || 'N/A';
                    
                    // Set delivery method
                    document.getElementById('delivery-method').textContent = 
                        orderData.deliveryMethod === 'delivery' ? 'Home Delivery' : 'Pick-up';
                    
                    // Show address for delivery, use N/A as default if blank
                    if (orderData.deliveryMethod === 'delivery') {
                        document.getElementById('address-row').style.display = 'block';
                        document.getElementById('customer-address').textContent = 
                            orderData.customerAddress && orderData.customerAddress.trim() ? orderData.customerAddress : 'N/A';
                    } else {
                        // Still display address row but show N/A for pickup
                        document.getElementById('address-row').style.display = 'block';
                        document.getElementById('customer-address').textContent = 'N/A';
                    }
                    
                    // Populate items table
                    const itemsContainer = document.getElementById('receipt-items');
                    itemsContainer.innerHTML = ''; // Clear sample items
                    
                    let totalAmount = 0;
                    
                    if (orderData.items && orderData.items.length > 0) {
                        orderData.items.forEach(item => {
                            const itemTotal = item.price * item.quantity;
                            totalAmount += itemTotal;
                            
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${item.name}</td>
                                <td>${item.quantity}</td>
                                <td>₱${item.price.toFixed(2)}</td>
                                <td>₱${itemTotal.toFixed(2)}</td>
                            `;
                            itemsContainer.appendChild(row);
                        });
                    } else {
                        // If no items, show a message
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="4" style="text-align: center;">No items in order</td>';
                        itemsContainer.appendChild(row);
                    }
                    
                    // Set total
                    document.getElementById('receipt-total').textContent = `₱${totalAmount.toFixed(2)}`;
                } else {
                    // No order data found, leave the sample data or PHP data
                    console.log('No order data found in URL parameters');
                }
            } catch (error) {
                console.error('Error parsing order data:', error);
            }
            <?php endif; ?>
        });
        
        // Helper function to format payment method text
        function getPaymentMethodText(method) {
            if (!method) return 'N/A';
            
            switch (method.toLowerCase()) {
                case 'cash':
                    return 'Cash';
                case 'gcash':
                    return 'GCash';
                default:
                    return method; // Return as-is if not recognized
            }
        }
    </script>
</body>
</html>