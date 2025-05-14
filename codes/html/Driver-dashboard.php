<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Retrieve transactions with delivery method = "delivery" or "Home Delivery"
$query = "SELECT t.*, t.DeliveryStatus, c.CustomerName, c.CustomerNumber, c.CustomerAddress, p.ProductName, p.ProductPrice 
          FROM Transaction t 
          JOIN Customer c ON t.CustomerID = c.CustomerID
          JOIN Product p ON p.ProductID = t.ProductID
          WHERE t.DeliveryMethod = 'Home Delivery' OR t.DeliveryMethod = 'delivery'
          ORDER BY t.TransactionDate DESC";
          
$result = $conn->query($query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Group transactions by TransactionID (to handle multiple products per order)
$orders = [];
while ($row = $result->fetch_assoc()) {
    $transactionId = $row['TransactionID'];
    
    if (!isset($orders[$transactionId])) {
        $orders[$transactionId] = [
            'id' => $transactionId,
            'customer_name' => $row['CustomerName'],
            'customer_number' => $row['CustomerNumber'],
            'address' => $row['CustomerAddress'],
            'date' => $row['TransactionDate'],
            'delivery_method' => $row['DeliveryMethod'],
            'delivery_status' => isset($row['DeliveryStatus']) ? $row['DeliveryStatus'] : '',
            'payment_method' => $row['PaymentMethod'],
            'items' => [],
            'total' => 0
        ];
    }
    
    // Group items by product name and sum quantity using the correct Quantity field
    $productName = $row['ProductName'];
    $productPrice = isset($row['Price']) ? $row['Price'] : (isset($row['ProductPrice']) ? $row['ProductPrice'] : 0);
    $productQuantity = isset($row['Quantity']) ? (int)$row['Quantity'] : 1;
    $found = false;
    foreach ($orders[$transactionId]['items'] as &$item) {
        if ($item['name'] === $productName) {
            $item['quantity'] += $productQuantity;
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) {
        $orders[$transactionId]['items'][] = [
            'name' => $productName,
            'price' => $productPrice, // Set price as unit price, not total
            'quantity' => $productQuantity
        ];
    }
    // Add to total
    $orders[$transactionId]['total'] += $productPrice * $productQuantity;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - RJane Water</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Css/Driver.css">
</head>

<body>
    <div class="dashboard">
        <header>
            <h1><i class="fas fa-water"></i> RJane Water Driver</h1>
            <div class="user-actions">
                <span class="driver-info">
                    <span>Driver: <span id="header-cashier-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Not logged in'; ?></span></span>
                <a href="../Controllers/Logout.php" class="action-btn logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
        <div class="action-toolbar">
            <div class="toolbar-tabs">
                <button id="tab-available" class="toolbar-tab active"><i class="fas fa-list"></i> Available Orders</button>
                <button id="tab-accepted" class="toolbar-tab"><i class="fas fa-check"></i> Accepted Orders</button>
            </div>
            <h2><i class="fas fa-clipboard-list"></i> <span id="orders-title">Available Orders</span></h2>
            <div class="toolbar-actions" id="toolbar-actions-available">
                <button id="select-all" class="toolbar-btn"><i class="fas fa-check-square"></i> Select All</button>
                <button id="accept-selected" class="toolbar-btn accept-btn"><i class="fas fa-truck"></i> Accept & Print Selected</button>
            </div>
            <div class="toolbar-actions" id="toolbar-actions-accepted" style="display:none;">
                <button id="select-all-accepted" class="toolbar-btn"><i class="fas fa-check-square"></i> Select All</button>
                <button id="mark-delivered-selected" class="toolbar-btn accept-btn"><i class="fas fa-check"></i> Mark Delivered Selected</button>
            </div>
        </div>
        <div class="orders" id="available-orders">
            <?php
            // Group orders by TransactionDate (date and time)
            $groupedOrders = [];
            $groupedAcceptedOrders = [];
            foreach ($orders as $order) {
                // Only include orders with DeliveryMethod = 'delivery' and DeliveryStatus = 'In Transit' for Available Orders
                if (strtolower($order['delivery_method']) === 'delivery' && strtolower($order['delivery_status'] ?? '') === 'in transit') {
                    $dateKey = date('M d, Y h:i A', strtotime($order['date']));
                    if (!isset($groupedOrders[$dateKey])) {
                        $groupedOrders[$dateKey] = [];
                    }
                    $groupedOrders[$dateKey][] = $order;
                }
                // For Accepted Orders tab: DeliveryMethod = 'delivery' and DeliveryStatus = 'pending'
                if (strtolower($order['delivery_method']) === 'delivery' && strtolower($order['delivery_status'] ?? '') === 'pending') {
                    $dateKey = date('M d, Y h:i A', strtotime($order['date']));
                    if (!isset($groupedAcceptedOrders[$dateKey])) {
                        $groupedAcceptedOrders[$dateKey] = [];
                    }
                    $groupedAcceptedOrders[$dateKey][] = $order;
                }
            }
            if (empty($groupedOrders)) {
                echo '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No delivery orders available at this time.</p></div>';
            } else {
                foreach ($groupedOrders as $dateKey => $ordersOnDate) {
                    foreach ($ordersOnDate as $order) {
                        ?>
                        <div class="order-card" data-order-id="RJ-<?php echo $order['id']; ?>">
                            <div class="order-checkbox">
                                <input type="checkbox" id="order-RJ-<?php echo $order['id']; ?>" class="order-select">
                                <label for="order-RJ-<?php echo $order['id']; ?>" class="select-indicator">
                                    <i class="fas fa-check"></i>
                                </label>
                            </div>
                            <div class="order-header">
                                <div>Order #RJ-<?php echo $order['id']; ?></div>
                                <div class="status status-pending">New</div>
                            </div>
                            <div class="order-body">
                                <div class="order-items">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <p>
                                            <span class="item-qty"><?php echo $item['quantity']; ?>x</span>
                                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span class="item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                                <div class="order-customer">
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_number']); ?></p>
                                </div>
                                <div class="order-address">
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['address']); ?></p>
                                </div>
                            </div>
                            <div class="order-footer">
                                <div>Total: ₱<?php echo number_format($order['total'], 2); ?></div>
                                <div>
                                    <button class="btn-accept" data-id="<?php echo $order['id']; ?>">Accept</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
        <div class="orders" id="accepted-orders" style="display:none;">
            <?php
            // Always freshly query for accepted orders (DeliveryMethod = 'Delivery' and DeliveryStatus = 'Pending')
            $acceptedQuery = "SELECT t.*, c.CustomerName, c.CustomerNumber, c.CustomerAddress, p.ProductName, p.ProductPrice 
                FROM Transaction t 
                JOIN Customer c ON t.CustomerID = c.CustomerID
                JOIN Product p ON p.ProductID = t.ProductID
                WHERE (t.DeliveryMethod = 'Delivery' OR t.DeliveryMethod = 'delivery')
                  AND t.DeliveryStatus = 'pending'
                ORDER BY t.TransactionDate DESC";
            $acceptedResult = $conn->query($acceptedQuery);
            $acceptedOrders = [];
            if ($acceptedResult) {                while ($row = $acceptedResult->fetch_assoc()) {
                    $transactionId = $row['TransactionID'];
                    if (!isset($acceptedOrders[$transactionId])) {
                        $acceptedOrders[$transactionId] = [
                            'id' => $transactionId,
                            'customer_name' => $row['CustomerName'],
                            'customer_number' => $row['CustomerNumber'],
                            'address' => $row['CustomerAddress'],
                            'date' => $row['TransactionDate'],
                            'delivery_method' => $row['DeliveryMethod'],
                            'payment_method' => $row['PaymentMethod'],
                            'items' => [],
                            'total' => 0
                        ];
                    }
                    
                    // Group items by product name and sum quantity using the correct Quantity field
                    $productName = $row['ProductName'];
                    $productPrice = isset($row['Price']) ? $row['Price'] : (isset($row['ProductPrice']) ? $row['ProductPrice'] : 0);
                    $productQuantity = isset($row['Quantity']) ? (int)$row['Quantity'] : 1;
                    $found = false;
                    foreach ($acceptedOrders[$transactionId]['items'] as &$item) {
                        if ($item['name'] === $productName) {
                            $item['quantity'] += $productQuantity;
                            $found = true;
                            break;
                        }
                    }
                    unset($item);
                    if (!$found) {
                        $acceptedOrders[$transactionId]['items'][] = [
                            'name' => $productName,
                            'price' => $productPrice, // Set price as unit price, not total
                            'quantity' => $productQuantity
                        ];
                    }
                    $acceptedOrders[$transactionId]['total'] += $productPrice * $productQuantity;
                }
            }
            if (empty($acceptedOrders)) {
                echo '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No accepted delivery orders at this time.</p></div>';
            } else {
                foreach ($acceptedOrders as $order) {
                    ?>
                    <div class="order-card" data-order-id="RJ-<?php echo $order['id']; ?>">
                        <div class="order-checkbox">
                            <input type="checkbox" id="order-accepted-RJ-<?php echo $order['id']; ?>" class="order-select-accepted">
                            <label for="order-accepted-RJ-<?php echo $order['id']; ?>" class="select-indicator">
                                <i class="fas fa-check"></i>
                            </label>
                        </div>
                        <div class="order-header">
                            <div>Order #RJ-<?php echo $order['id']; ?></div>
                            <div class="status status-accepted">Accepted</div>
                        </div>
                        <div class="order-body">
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <p>
                                        <span class="item-qty"><?php echo $item['quantity']; ?>x</span>
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                            <div class="order-customer">
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_number']); ?></p>
                            </div>
                            <div class="order-address">
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['address']); ?></p>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div>Total: ₱<?php echo number_format($order['total'], 2); ?></div>
                            <div>
                                <button class="btn-delivered" data-id="<?php echo $order['id']; ?>">Delivered</button>
                                <button class="btn-cancel" data-id="<?php echo $order['id']; ?>">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove Print Selected button
            const printBtn = document.getElementById('print-selected');
            if (printBtn) printBtn.remove();

            // Select all button functionality
            const selectAllButton = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-select');
            let allSelected = false;
            
            selectAllButton.addEventListener('click', function() {
                allSelected = !allSelected;
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = allSelected;
                    updateOrderCardSelection(checkbox);
                });
                
                // Update button text
                selectAllButton.innerHTML = allSelected ? 
                    '<i class="fas fa-square"></i> Deselect All' :
                    '<i class="fas fa-check-square"></i> Select All';
            });
            
            // Individual checkbox functionality
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateOrderCardSelection(this);
                    
                    // Check if all are selected
                    const totalCheckboxes = document.querySelectorAll('.order-select').length;
                    const selectedCheckboxes = document.querySelectorAll('.order-select:checked').length;
                    
                    if (selectedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
                        selectAllButton.innerHTML = '<i class="fas fa-square"></i> Deselect All';
                        allSelected = true;
                    } else {
                        selectAllButton.innerHTML = '<i class="fas fa-check-square"></i> Select All';
                        allSelected = false;
                    }
                });
            });
            
            // Helper function to update order card highlighting
            function updateOrderCardSelection(checkbox) {
                const orderCard = checkbox.closest('.order-card');
                if (orderCard) {
                    const orderHeader = orderCard.querySelector('.order-header');
                    
                    if (checkbox.checked) {
                        orderHeader.classList.add('selected');
                    } else {
                        orderHeader.classList.remove('selected');
                    }
                }
            }

            // Accept & Print Selected functionality
            document.getElementById('accept-selected').addEventListener('click', function() {
                const selectedOrders = document.querySelectorAll('.order-select:checked');
                
                if (selectedOrders.length === 0) {
                    alert('Please select at least one order to accept and print');
                    return;
                }
                
                // First print the selected orders
                // Hide all non-selected orders before printing
                document.querySelectorAll('.order-card').forEach(card => {
                    const checkbox = card.querySelector('.order-select');
                    if (checkbox && !checkbox.checked) {
                        card.style.display = 'none';
                    }
                });
                
                // Hide buttons and checkboxes before printing
                document.querySelectorAll('.order-checkbox, .btn-accept, .btn-skip, .action-toolbar').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Save original orders container display style
                const ordersContainer = document.getElementById('available-orders');
                const originalDisplayStyle = window.getComputedStyle(ordersContainer).display;
                
                // Print the selected orders
                window.print();
                
                // Show all elements again
                document.querySelectorAll('.order-card').forEach(card => {
                    card.style.display = 'flex';
                });
                document.querySelectorAll('.order-checkbox, .btn-accept, .btn-skip, .action-toolbar').forEach(el => {
                    el.style.display = '';
                });
                  // Explicitly restore grid layout
                ordersContainer.style.display = originalDisplayStyle;
                ordersContainer.classList.add('grid-restored');
                
                // Force a reflow to apply the styles correctly
                void ordersContainer.offsetWidth;
                
                // Remove the class after a delay to avoid conflicting with other styles
                setTimeout(() => {
                    ordersContainer.classList.remove('grid-restored');
                }, 300);
                
                // Then mark them as accepted (update status to 'pending' via AJAX)
                const transactionIds = [];
                const orderCardsToRemove = [];
                
                selectedOrders.forEach(checkbox => {
                    const orderCard = checkbox.closest('.order-card');
                    if (orderCard) {
                        const acceptBtn = orderCard.querySelector('.btn-accept');
                        const transactionId = acceptBtn.getAttribute('data-id');
                        if (transactionId) {
                            transactionIds.push(transactionId);
                            orderCardsToRemove.push(orderCard);
                        }
                    }
                });
                
                // AJAX call to update all selected orders to 'pending'
                fetch('../controllers/update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_ids: transactionIds, status: 'pending' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${orderCardsToRemove.length} order(s) have been accepted for delivery.`);
                        
                        // Process each order card
                        orderCardsToRemove.forEach(card => {
                            const transactionId = card.querySelector('.btn-accept').getAttribute('data-id');
                            const orderId = card.getAttribute('data-order-id');
                            
                            // Clone the order card
                            const clonedCard = card.cloneNode(true);
                            
                            // Update the status in the cloned card
                            const statusDiv = clonedCard.querySelector('.status');
                            statusDiv.textContent = 'Accepted';
                            statusDiv.classList.remove('status-pending');
                            statusDiv.classList.add('status-accepted');
                            
                            // Replace Accept button with Delivered and Cancel buttons
                            const footerBtns = clonedCard.querySelector('.order-footer > div:last-child');
                            footerBtns.innerHTML = `
                                <button class="btn-delivered" data-id="${transactionId}">Delivered</button>
                                <button class="btn-cancel" data-id="${transactionId}">Cancel</button>
                            `;
                            
                            // Update checkbox
                            const checkbox = clonedCard.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.className = 'order-select-accepted';
                                checkbox.id = `order-accepted-RJ-${transactionId}`;
                                checkbox.checked = false;
                                const label = clonedCard.querySelector('label');
                                if (label) {
                                    label.setAttribute('for', `order-accepted-RJ-${transactionId}`);
                                }
                            }
                            
                            // Remove from Available Orders
                            card.remove();
                            
                            // Add to Accepted Orders
                            const acceptedOrdersContainer = document.getElementById('accepted-orders');
                            
                            // Check if there's a "no orders" message
                            const noOrdersMessage = acceptedOrdersContainer.querySelector('.no-orders');
                            if (noOrdersMessage) {
                                noOrdersMessage.remove();
                            }
                            
                            acceptedOrdersContainer.appendChild(clonedCard);
                            
                            // Add event listeners to the new buttons
                            const deliveredBtn = clonedCard.querySelector('.btn-delivered');
                            const cancelBtn = clonedCard.querySelector('.btn-cancel');
                            
                            if (deliveredBtn) {
                                deliveredBtn.addEventListener('click', handleDeliveredClick);
                            }
                            
                            if (cancelBtn) {
                                cancelBtn.addEventListener('click', handleCancelClick);
                            }
                            
                            // Make the card selectable
                            const newCheckbox = clonedCard.querySelector('.order-select-accepted');
                            if (newCheckbox) {
                                newCheckbox.addEventListener('change', function() {
                                    updateAcceptedOrderCardSelection(this);
                                });
                            }
                            
                            // Make card clickable
                            clonedCard.addEventListener('click', function(e) {
                                if (e.target.closest('.btn-delivered') || 
                                    e.target.closest('.btn-cancel') || 
                                    e.target.closest('.order-checkbox')) {
                                    return;
                                }
                                
                                const checkbox = this.querySelector('.order-select-accepted');
                                if (checkbox) {
                                    checkbox.checked = !checkbox.checked;
                                    const event = new Event('change');
                                    checkbox.dispatchEvent(event);
                                }
                            });
                        });
                        
                        // Check if there are no more available orders
                        if (availableOrders.querySelectorAll('.order-card').length === 0) {
                            availableOrders.innerHTML = '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No delivery orders available at this time.</p></div>';
                        }
                    } else {
                        alert('Failed to update order status.');
                    }
                })
                .catch(() => {
                    alert('Failed to update order status.');
                });
            });
            
            // Accept order functionality (print and set status to pending)
            document.querySelectorAll('.btn-accept').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const orderCard = this.closest('.order-card');
                    const orderId = orderCard.getAttribute('data-order-id');
                    const transactionId = this.getAttribute('data-id');
                    // Hide all other cards for printing
                    document.querySelectorAll('.order-card').forEach(card => {
                        if (card !== orderCard) card.style.display = 'none';
                    });
                    // Hide checkboxes and buttons
                    document.querySelectorAll('.order-checkbox, .btn-accept, .btn-skip, .action-toolbar').forEach(el => {
                        el.style.display = 'none';
                    });
                    // Print
                    window.print();
                    // Restore all cards and controls
                    document.querySelectorAll('.order-card').forEach(card => {
                        card.style.display = 'flex';
                    });
                    document.querySelectorAll('.order-checkbox, .btn-accept, .btn-skip, .action-toolbar').forEach(el => {
                        el.style.display = '';
                    });
                    // Update status in DB via AJAX
                    fetch('../controllers/update_order_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transaction_id: transactionId, status: 'pending' })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Order ${orderId} has been accepted for delivery.`);
                            
                            // Clone the order card before removing it from Available
                            const clonedCard = orderCard.cloneNode(true);
                            
                            // Update the status in the cloned card
                            const statusDiv = clonedCard.querySelector('.status');
                            statusDiv.textContent = 'Accepted';
                            statusDiv.classList.remove('status-pending');
                            statusDiv.classList.add('status-accepted');
                            
                            // Replace Accept button with Delivered and Cancel buttons
                            const footerBtns = clonedCard.querySelector('.order-footer > div:last-child');
                            footerBtns.innerHTML = `
                                <button class="btn-delivered" data-id="${transactionId}">Delivered</button>
                                <button class="btn-cancel" data-id="${transactionId}">Cancel</button>
                            `;
                            
                            // Update checkbox
                            const checkbox = clonedCard.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.className = 'order-select-accepted';
                                checkbox.id = `order-accepted-RJ-${transactionId}`;
                                const label = clonedCard.querySelector('label');
                                if (label) {
                                    label.setAttribute('for', `order-accepted-RJ-${transactionId}`);
                                }
                            }
                            
                            // Remove from Available Orders
                            orderCard.remove();
                            
                            // Add to Accepted Orders
                            const acceptedOrdersContainer = document.getElementById('accepted-orders');
                            
                            // Check if there's a "no orders" message
                            const noOrdersMessage = acceptedOrdersContainer.querySelector('.no-orders');
                            if (noOrdersMessage) {
                                noOrdersMessage.remove();
                            }
                            
                            acceptedOrdersContainer.appendChild(clonedCard);
                            
                            // Add event listeners to the new buttons
                            const deliveredBtn = clonedCard.querySelector('.btn-delivered');
                            const cancelBtn = clonedCard.querySelector('.btn-cancel');
                            
                            if (deliveredBtn) {
                                deliveredBtn.addEventListener('click', handleDeliveredClick);
                            }
                            
                            if (cancelBtn) {
                                cancelBtn.addEventListener('click', handleCancelClick);
                            }
                            
                            // Make the card selectable
                            const newCheckbox = clonedCard.querySelector('.order-select-accepted');
                            if (newCheckbox) {
                                newCheckbox.addEventListener('change', function() {
                                    updateAcceptedOrderCardSelection(this);
                                });
                            }
                            
                            // Make card clickable
                            clonedCard.addEventListener('click', function(e) {
                                if (e.target.closest('.btn-delivered') || 
                                    e.target.closest('.btn-cancel') || 
                                    e.target.closest('.order-checkbox')) {
                                    return;
                                }
                                
                                const checkbox = this.querySelector('.order-select-accepted');
                                if (checkbox) {
                                    checkbox.checked = !checkbox.checked;
                                    const event = new Event('change');
                                    checkbox.dispatchEvent(event);
                                }
                            });
                        } else {
                            alert('Failed to update order status.');
                        }
                    })
                    .catch(() => {
                        alert('Failed to update order status.');
                    });
                });
            });

            // Skip order functionality
            document.querySelectorAll('.btn-skip').forEach(button => {
                button.addEventListener('click', function() {
                    const orderCard = this.closest('.order-card');
                    const orderId = orderCard.getAttribute('data-order-id');
                    
                    // You can add AJAX call here to update the order status in the database
                    orderCard.remove();
                });
            });
            
            // Order card click functionality
            document.querySelectorAll('.order-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons or checkbox directly
                    if (e.target.closest('.btn-accept') || 
                        e.target.closest('.btn-skip') || 
                        e.target.closest('.order-checkbox')) {
                        return;
                    }
                    
                    // Toggle the checkbox
                    const checkbox = this.querySelector('.order-select');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        
                        // Trigger the change event
                        const event = new Event('change');
                        checkbox.dispatchEvent(event);
                    }
                });
            });

            // Tab switching logic
            const tabAvailable = document.getElementById('tab-available');
            const tabAccepted = document.getElementById('tab-accepted');
            const ordersTitle = document.getElementById('orders-title');
            const availableOrders = document.getElementById('available-orders');
            const acceptedOrders = document.getElementById('accepted-orders');
            const toolbarActionsAvailable = document.getElementById('toolbar-actions-available');
            const toolbarActionsAccepted = document.getElementById('toolbar-actions-accepted');
            // Default to Available Orders
            tabAvailable.classList.add('active');
            tabAccepted.classList.remove('active');
            ordersTitle.textContent = 'Available Orders';
            availableOrders.style.display = '';
            acceptedOrders.style.display = 'none';
            toolbarActionsAvailable.style.display = '';
            toolbarActionsAccepted.style.display = 'none';
            tabAvailable.addEventListener('click', function() {
                tabAvailable.classList.add('active');
                tabAccepted.classList.remove('active');
                ordersTitle.textContent = 'Available Orders';
                availableOrders.style.display = '';
                acceptedOrders.style.display = 'none';
                toolbarActionsAvailable.style.display = '';
                toolbarActionsAccepted.style.display = 'none';
            });
            tabAccepted.addEventListener('click', function() {
                tabAccepted.classList.add('active');
                tabAvailable.classList.remove('active');
                ordersTitle.textContent = 'Accepted Orders';
                availableOrders.style.display = 'none';
                acceptedOrders.style.display = '';
                toolbarActionsAvailable.style.display = 'none';
                toolbarActionsAccepted.style.display = '';
            });
            // Select All for Accepted Orders
            let allAcceptedSelected = false;
            document.getElementById('select-all-accepted').addEventListener('click', function() {
                allAcceptedSelected = !allAcceptedSelected;
                
                const acceptedCheckboxes = acceptedOrders.querySelectorAll('.order-select-accepted');
                acceptedCheckboxes.forEach(checkbox => {
                    checkbox.checked = allAcceptedSelected;
                    updateAcceptedOrderCardSelection(checkbox);
                });
                
                this.innerHTML = allAcceptedSelected ? 
                    '<i class="fas fa-square"></i> Deselect All' : 
                    '<i class="fas fa-check-square"></i> Select All';
            });
            
            // Helper function to update accepted order card highlighting
            function updateAcceptedOrderCardSelection(checkbox) {
                const orderCard = checkbox.closest('.order-card');
                if (orderCard) {
                    const orderHeader = orderCard.querySelector('.order-header');
                    
                    if (checkbox.checked) {
                        orderHeader.classList.add('selected');
                    } else {
                        orderHeader.classList.remove('selected');
                    }
                }
            }
            
            // Individual checkbox functionality for accepted orders
            acceptedOrders.querySelectorAll('.order-select-accepted').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateAcceptedOrderCardSelection(this);
                });
            });
            
            // Make accepted order cards clickable for selection
            acceptedOrders.querySelectorAll('.order-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons or checkbox directly
                    if (e.target.closest('.btn-delivered') || 
                        e.target.closest('.btn-cancel') || 
                        e.target.closest('.order-checkbox')) {
                        return;
                    }
                    
                    // Toggle the checkbox
                    const checkbox = this.querySelector('.order-select-accepted');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        
                        // Trigger the change event
                        const event = new Event('change');
                        checkbox.dispatchEvent(event);
                    }
                });
            });
            
            // Mark Delivered Selected
            document.getElementById('mark-delivered-selected').addEventListener('click', function() {
                const selectedCards = Array.from(acceptedOrders.querySelectorAll('.order-select-accepted:checked')).map(cb => cb.closest('.order-card'));
                if (selectedCards.length === 0) {
                    alert('Please select at least one order to mark as delivered.');
                    return;
                }
                const transactionIds = selectedCards.map(card => card.getAttribute('data-order-id').replace('RJ-', ''));
                fetch('../controllers/update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_ids: transactionIds, status: 'Delivered' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(`${selectedCards.length} order(s) marked as Delivered.`);
                        // Remove the cards from the UI
                        selectedCards.forEach(card => {
                            card.remove();
                        });
                        
                        // Check if there are no more accepted orders
                        if (acceptedOrders.querySelectorAll('.order-card').length === 0) {
                            acceptedOrders.innerHTML = '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No accepted delivery orders at this time.</p></div>';
                        }
                    } else {
                        alert('Failed to update order status.');
                    }
                })
                .catch(() => {
                    alert('Failed to update order status.');
                });
            });
            
            // Delivered button logic for Accepted Orders tab
            document.querySelectorAll('.btn-delivered').forEach(button => {
                button.addEventListener('click', handleDeliveredClick);
            });
            
            // Add Cancel button logic
            document.querySelectorAll('.btn-cancel').forEach(button => {
                button.addEventListener('click', handleCancelClick);
            });
            
            // Helper functions for button clicks
            function handleDeliveredClick(e) {
                e.stopPropagation();
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.getAttribute('data-order-id');
                const transactionId = this.getAttribute('data-id');
                
                fetch('../Controllers/update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_id: transactionId, status: 'Delivered' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(`Order ${orderId} has been marked as Delivered.`);
                        orderCard.remove();
                        
                        // Check if there are no more accepted orders
                        if (acceptedOrders.querySelectorAll('.order-card').length === 0) {
                            acceptedOrders.innerHTML = '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No accepted delivery orders at this time.</p></div>';
                        }
                    } else {
                        alert('Failed to update order status.');
                    }
                })
                .catch(() => {
                    alert('Failed to update order status.');
                });
            }
            
            function handleCancelClick(e) {
                e.stopPropagation();
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.getAttribute('data-order-id');
                const transactionId = this.getAttribute('data-id');
                
                fetch('../Controllers/update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_id: transactionId, status: 'In Transit' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(`Order ${orderId} has been moved back to Available.`);
                        
                        // Clone the order card before removing it from Accepted
                        const clonedCard = orderCard.cloneNode(true);
                        
                        // Update the status in the cloned card
                        const statusDiv = clonedCard.querySelector('.status');
                        statusDiv.textContent = 'New';
                        statusDiv.classList.remove('status-accepted');
                        statusDiv.classList.add('status-pending');
                        
                        // Replace Delivered and Cancel buttons with Accept button
                        const footerBtns = clonedCard.querySelector('.order-footer > div:last-child');
                        footerBtns.innerHTML = `
                            <button class="btn-accept" data-id="${transactionId}">Accept</button>
                        `;
                        
                        // Update checkbox
                        const checkbox = clonedCard.querySelector('input[type="checkbox"]');
                        if (checkbox) {
                            checkbox.className = 'order-select';
                            checkbox.id = `order-RJ-${transactionId}`;
                            const label = clonedCard.querySelector('label');
                            if (label) {
                                label.setAttribute('for', `order-RJ-${transactionId}`);
                            }
                        }
                        
                        // Remove from Accepted Orders
                        orderCard.remove();
                        
                        // Add to Available Orders
                        const availableOrdersContainer = document.getElementById('available-orders');
                        
                        // Check if there's a "no orders" message
                        const noOrdersMessage = availableOrdersContainer.querySelector('.no-orders');
                        if (noOrdersMessage) {
                            noOrdersMessage.remove();
                        }
                        
                        availableOrdersContainer.appendChild(clonedCard);
                        
                        // Check if there are no more accepted orders
                        if (acceptedOrders.querySelectorAll('.order-card').length === 0) {
                            acceptedOrders.innerHTML = '<div class="no-orders"><i class="fas fa-info-circle"></i><p>No accepted delivery orders at this time.</p></div>';
                        }
                        
                        // Add event listener to the new Accept button
                        const acceptBtn = clonedCard.querySelector('.btn-accept');
                        if (acceptBtn) {
                            acceptBtn.addEventListener('click', function(e) {
                                document.querySelectorAll('.btn-accept').forEach(btn => {
                                    if (btn.getAttribute('data-id') === this.getAttribute('data-id')) {
                                        btn.click();
                                    }
                                });
                            });
                        }
                        
                        // Make the card selectable
                        const newCheckbox = clonedCard.querySelector('.order-select');
                        if (newCheckbox) {
                            newCheckbox.addEventListener('change', function() {
                                updateOrderCardSelection(this);
                            });
                        }
                        
                        // Make card clickable
                        clonedCard.addEventListener('click', function(e) {
                            if (e.target.closest('.btn-accept') || 
                                e.target.closest('.order-checkbox')) {
                                return;
                            }
                            
                            const checkbox = this.querySelector('.order-select');
                            if (checkbox) {
                                checkbox.checked = !checkbox.checked;
                                const event = new Event('change');
                                checkbox.dispatchEvent(event);
                            }
                        });
                    } else {
                        alert('Failed to update order status.');
                    }
                })
                .catch(() => {
                    alert('Failed to update order status.');
                });
            }
        });
    </script>
</body>
</html>