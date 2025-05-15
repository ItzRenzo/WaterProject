<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Get cashier name for display
$Cashier = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
if (isset($_SESSION['user_id'])) {
    error_log('User ID found: ' . $_SESSION['user_id']);

    // If we already have the username in the session, use it
    if (isset($_SESSION['username'])) {
        $cashier_name = htmlspecialchars($_SESSION['username']);
        error_log('Using username from session: ' . $cashier_name);
    } else {
        // Get the employee name from the database using the session user_id
        $stmt = $conn->prepare('SELECT CONCAT(FirstName, " ", LastName) AS FullName FROM Employee WHERE EmployeeID = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            $cashier_name = htmlspecialchars($row['FullName']);
            error_log('Got username from database: ' . $cashier_name);
        } else {
            $cashier_name = 'Unknown Employee';
            error_log('No matching user found in database');
        }
        $stmt->close();
    }
} else {
    error_log('No user_id in session');
}

// Get product stock information
$product_stocks = [];
try {
    $stockStmt = $conn->prepare("SELECT ProductName, Stocks FROM Product WHERE Stocks > 0");
    if ($stockStmt && $stockStmt->execute()) {
        $stockResult = $stockStmt->get_result();
        while ($row = $stockResult->fetch_assoc()) {
            $product_stocks[$row['ProductName']] = (int)$row['Stocks'];
        }
        $stockStmt->close();
    }
} catch (Exception $e) {
    error_log('Error fetching product stocks: ' . $e->getMessage());
}

// Pass data from PHP to JavaScript (only for GET requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo '<script>
        // Pass data from PHP to JavaScript
        var PHP_CASHIER_ID = ' . (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : '0') . ';
        var PHP_CASHIER_NAME = "' . addslashes($cashier_name) . '";
        var PHP_PRODUCT_STOCKS = ' . json_encode($product_stocks) . ';
    </script>';
}

// Handle POST requests (AJAX order submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['from_cashier'])) {
    $cashier_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    if (!$cashier_id) {
        $response = ['success' => false, 'message' => 'Session expired or not logged in. Please log in again.'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    $customer_name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $customer_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $payment_method = isset($_POST['payment']) ? trim($_POST['payment']) : 'cash';
    $delivery_method = isset($_POST['delivery']) ? trim($_POST['delivery']) : 'pickup';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $cart_total = isset($_POST['cart_total']) ? floatval($_POST['cart_total']) : 0;
    $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
    $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];
    if ($delivery_method === 'pickup') $address = 'N/A';
    if (empty($customer_name) || empty($customer_phone) || empty($cart_items)) {
        $response = ['success' => false, 'message' => 'Missing required order information.'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    if ($payment_amount < $cart_total) {
        $response = ['success' => false, 'message' => 'Insufficient payment amount.'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    try {
        $conn->begin_transaction();
        // 1. Retrieve or update customer
        $customer_id = null;
        $checkCustomerStmt = $conn->prepare("SELECT CustomerID, CustomerAddress FROM Customer WHERE CustomerName = ? AND CustomerNumber = ?");
        $checkCustomerStmt->bind_param('ss', $customer_name, $customer_phone);
        $checkCustomerStmt->execute();
        $customerResult = $checkCustomerStmt->get_result();
        if ($customerResult->num_rows > 0) {
            $customerRow = $customerResult->fetch_assoc();
            $customer_id = $customerRow['CustomerID'];
            // Update address if changed
            if ($customerRow['CustomerAddress'] !== $address) {
                $updateAddressStmt = $conn->prepare("UPDATE Customer SET CustomerAddress = ? WHERE CustomerID = ?");
                $updateAddressStmt->bind_param('si', $address, $customer_id);
                $updateAddressStmt->execute();
                $updateAddressStmt->close();
            }
        } else {
            $newCustomerStmt = $conn->prepare("INSERT INTO Customer (CustomerName, CustomerNumber, CustomerAddress) VALUES (?, ?, ?)");
            $newCustomerStmt->bind_param('sss', $customer_name, $customer_phone, $address);
            $newCustomerStmt->execute();
            $customer_id = $conn->insert_id;
            $newCustomerStmt->close();
        }
        $checkCustomerStmt->close();
        // 2. For each cart item, insert a transaction row and update stock
        foreach ($cart_items as $item) {
            $item_name = $item['name'];
            $item_quantity = $item['quantity'];
            // Get ProductID and price
            $productStmt = $conn->prepare("SELECT ProductID, ProductPrice, Stocks FROM Product WHERE ProductName = ?");
            $productStmt->bind_param('s', $item_name);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            if ($productResult->num_rows > 0) {
                $productRow = $productResult->fetch_assoc();
                $product_id = $productRow['ProductID'];
                $product_price = (float)$productRow['ProductPrice'];
                $available_stock = (int)$productRow['Stocks'];
                if ($available_stock < $item_quantity) {
                    throw new Exception("Not enough stock for {$item_name}. Only {$available_stock} available.");
                }
                $total_price = $product_price * $item_quantity;
                // Set delivery status
                if (strtolower($delivery_method) === 'pickup') {
                    $delivery_status = 'Completed';
                } else {
                    $delivery_status = ($delivery_method === 'delivery' || $delivery_method === 'Delivery') ? 'pending' : 'N/A';
                }
                // Insert transaction for this product (now with quantity)
                $transactionStmt = $conn->prepare("INSERT INTO Transaction (CustomerID, ProductID, Price, Quantity, PaymentMethod, DeliveryMethod, DeliveryStatus) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $transactionStmt->bind_param('iidisss', $customer_id, $product_id, $total_price, $item_quantity, $payment_method, $delivery_method, $delivery_status);
                $transactionStmt->execute();
                $transactionStmt->close();
                // Update stock
                $updateStockStmt = $conn->prepare("UPDATE Product SET Stocks = Stocks - ? WHERE ProductID = ?");
                $updateStockStmt->bind_param('ii', $item_quantity, $product_id);
                $updateStockStmt->execute();
                $updateStockStmt->close();
                // --- Update Water Tank Level in DB ---
                $liters_used = 0;
                if ($product_id >= 1 && $product_id <= 4) {
                    $liters_used = 9 * $item_quantity;
                } elseif ($product_id >= 5 && $product_id <= 8) {
                    $liters_used = 0.5 * $item_quantity;
                }
                if ($liters_used > 0) {
                    $conn->query("UPDATE WaterTankLog SET tank_level = GREATEST(0, tank_level - $liters_used) ORDER BY last_refill DESC, id DESC LIMIT 1");
                }
                // --- End Water Tank Update ---
            } else {
                throw new Exception("Product {$item_name} not found.");
            }
            $productStmt->close();
        }
        $conn->commit();
        $response = [
            'success' => true,
            'message' => 'Order successfully processed and saved to database.'
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} elseif (isset($_POST['from_cashier'])) {
    // If it's coming from Cashier.php, just display the page - no AJAX response needed
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Water - RJane Water Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Css/order.css">
</head>

<body>
    <div class="kiosk-container">
        <!-- Header with back button -->
        <header class="kiosk-header">
            <a href="Cashier.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Order Water</h1> <!-- Cashier name display -->
            <div class="cashier-info">
                <span>Cashier: <span id="header-cashier-name"><?php echo htmlspecialchars($Cashier); ?></span></span>
            </div>

            <!-- Added logout link -->
            <a href="../Controllers/Logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </header>

        <!-- Main Content -->
        <main class="kiosk-main">
            <div class="order-tabs">
                <div class="tab active" data-tab="products">1. Select Product</div>
                <div class="tab" data-tab="customer">2. Your Details</div>
                <div class="tab" data-tab="payment">3. Payment</div>
                <div class="tab" data-tab="review">4. Review Order</div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="tab-products">
                <h3 class="section-title">Gallon Containers</h3>
                <div class="products-grid">
                    <!-- Product 1 -->
                    <div class="product-card" data-id="mineral" data-price="35.00">
                        <div class="product-img">
                            <img src="../../images/galon.png" alt="Mineral Water Gallon" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Mineral Water</h3>
                            <div class="price">₱35.00</div>
                            <div class="description">Natural minerals and electrolytes</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="product-card" data-id="purified" data-price="25.00">
                        <div class="product-img">
                            <img src="../../images/galon.png" alt="Purified Water Gallon" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Purified Water</h3>
                            <div class="price">₱25.00</div>
                            <div class="description">Clean and pure drinking water</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 3 -->
                    <div class="product-card" data-id="alkaline" data-price="45.00">
                        <div class="product-img">
                            <img src="../../images/galon.png" alt="Alkaline Water Gallon" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Alkaline Water</h3>
                            <div class="price">₱45.00</div>
                            <div class="description">Higher pH for balanced health</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 4 -->
                    <div class="product-card" data-id="distilled" data-price="30.00">
                        <div class="product-img">
                            <img src="../../images/galon.png" alt="Distilled Water Gallon" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Distilled Water</h3>
                            <div class="price">₱30.00</div>
                            <div class="description">Pure H2O with no minerals</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title">Plastic Bottles</h3>
                <div class="products-grid">
                    <!-- Bottle Product 1 -->
                    <div class="product-card" data-id="mineral-bottle" data-price="15.00">
                        <div class="product-img">
                            <img src="../../images/plastic-bottle.png" alt="Mineral Water Bottle" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Mineral Water Bottle</h3>
                            <div class="price">₱15.00</div>
                            <div class="description">500ml bottle with minerals</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Bottle Products 2-4 remain the same -->
                    <div class="product-card" data-id="purified-bottle" data-price="12.00">
                        <div class="product-img">
                            <img src="../../images/plastic-bottle.png" alt="Purified Water Bottle"
                                class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Purified Water Bottle</h3>
                            <div class="price">₱12.00</div>
                            <div class="description">500ml clean purified water</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="product-card" data-id="alkaline-bottle" data-price="18.00">
                        <div class="product-img">
                            <img src="../../images/plastic-bottle.png" alt="Alkaline Water Bottle"
                                class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Alkaline Water Bottle</h3>
                            <div class="price">₱18.00</div>
                            <div class="description">500ml high pH water</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="product-card" data-id="distilled-bottle" data-price="14.00">
                        <div class="product-img">
                            <img src="../../images/plastic-bottle.png" alt="Distilled Water Bottle"
                                class="product-image">
                        </div>
                        <div class="product-info">
                            <h3>Distilled Water Bottle</h3>
                            <div class="price">₱14.00</div>
                            <div class="description">500ml pure distilled water</div>
                            <div class="product-actions">
                                <div class="quantity-inline">
                                    <button class="qty-small-btn decrease-inline">-</button>
                                    <span class="qty-inline-value">1</span>
                                    <button class="qty-small-btn increase-inline">+</button>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart">Add</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cart-container" id="cart-container">
                    <div class="cart-header">
                        <h3>Your Order</h3>
                        <span class="cart-count">0</span>
                    </div>
                    <div id="cart-items">
                        <!-- Cart items will be displayed here -->
                        <div class="empty-cart">Cart is empty. Please add products.</div>
                    </div>
                    <div class="cart-footer">
                        <span>Total:</span>
                        <span id="cart-total">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- Other tabs are hidden by default -->
            <div class="tab-content" id="tab-customer" style="display: none;">
                <form id="customer-form">
                    <input type="hidden" id="cashier" name="cashier" value="<?php echo htmlspecialchars($Cashier); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="payment">Payment Option</label>
                            <select id="payment" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">Gcash</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="delivery">Delivery Option</label>
                            <select id="delivery" required>
                                <option value="pickup">Pick-up</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address (For Delivery)</label>
                            <textarea id="address" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Payment Tab -->
            <div class="tab-content" id="tab-payment" style="display: none;">
                <div class="payment-container">
                    <div class="payment-summary">
                        <div class="summary-header">Payment Details</div>
                        <div class="summary-item">
                            <span>Order Total:</span>
                            <span id="payment-order-total">₱0.00</span>
                        </div>

                        <div class="payment-input">
                            <div class="form-group">
                                <label for="payment-amount">Payment Amount:</label>
                                <div class="input-with-icon peso-prefix">
                                    <input type="number" id="payment-amount" min="0" step="1"
                                        placeholder="Enter amount">
                                </div>
                            </div>

                            <div class="change-calculation">
                                <div class="summary-item change">
                                    <span>Change:</span>
                                    <span id="change-amount">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-methods">
                        <h4>Quick Payment</h4>
                        <div class="quick-payment-grid">
                            <button class="quick-payment" data-amount="100">₱100</button>
                            <button class="quick-payment" data-amount="200">₱200</button>
                            <button class="quick-payment" data-amount="500">₱500</button>
                            <button class="quick-payment" data-amount="1000">₱1000</button>
                            <button class="quick-payment exact-amount">Exact Amount</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Review Tab -->
            <div class="tab-content" id="tab-review" style="display: none;">
                <div class="order-summary">
                    <div class="summary-header">Order Summary</div>
                    <div id="summary-product-name" class="summary-item">
                        <span>Product:</span>
                        <span>Mineral Water</span>
                    </div>
                    <div id="summary-quantity" class="summary-item">
                        <span>Quantity:</span>
                        <span>1</span>
                    </div>
                    <div id="summary-price" class="summary-item">
                        <span>Price per unit:</span>
                        <span>₱35.00</span>
                    </div>
                    <div id="summary-delivery" class="summary-item">
                        <span>Delivery:</span>
                        <span>Pick-up</span>
                    </div>
                    <div id="summary-total" class="summary-item total">
                        <span>Total:</span>
                        <span>₱35.00</span>
                    </div>
                </div>

                <div class="customer-details">
                    <h4>Customer Information</h4>
                    <p id="summary-customer-name">Name: </p>
                    <p id="summary-customer-phone">Phone: </p>
                    <p id="summary-customer-payment">Payment Method: </p>
                    <p id="summary-customer-delivery">Delivery Method: </p>
                    <p id="summary-customer-address">Address: </p>
                    <p id="summary-customer-quantity">Quantity: </p>
                    <p id="summary-customer-cashier">
                        Cashier: <span id="cashier-name-display" data-cashier-name="true">Not logged in</span>
                    </p>
                </div>
            </div>

            <!-- Order Success Tab -->
            <div class="tab-content" id="tab-success" style="display: none;">
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Order Placed Successfully!</h2>
                    <p>Thank you for your order. Your water will be ready for pickup/delivery shortly.</p>
                    <a href="Reciept.php?latest=true" class="Receipt-link">
                        <i class="fas fa-receipt"></i> Receipt
                    </a>
                </div>
            </div>
        </main> <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <button class="btn btn-secondary" id="prev-btn">Back</button>
            <button class="btn btn-primary" id="next-btn">Continue</button>
            <!-- Order data will be saved to database when clicking 'Place Order' on review tab -->
        </div>
    </div> <!-- PHP code for server-side cart processing will be added separately -->
    <!-- Add this script tag just before the closing </body> tag -->
    <script src="../../js/order.js"></script>
</body>

</html>