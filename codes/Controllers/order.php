<?php
$response = array(
    'success' => false,
    'message' => '',
    'errors' => array()
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $payment = isset($_POST['payment']) ? trim($_POST['payment']) : '';
    $delivery = isset($_POST['delivery']) ? trim($_POST['delivery']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $cartItems = isset($_POST['cart_items']) ? $_POST['cart_items'] : '';
    
    // Decode cart items
    $cartItemsArr = array();
    if (!empty($cartItems)) {
        $cartItemsArr = json_decode($cartItems, true);
        if (!is_array($cartItemsArr)) {
            $cartItemsArr = array();
        }
    }

    // Calculate order total securely
    $orderTotal = 0;
    foreach ($cartItemsArr as $item) {
        $itemPrice = isset($item['price']) ? floatval($item['price']) : 0;
        $itemQty = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $orderTotal += $itemPrice * $itemQty;
    }

    // Validation
    if (empty($name)) {
        $response['errors']['name'] = 'Name is required';
    }
    if (empty($phone)) {
        $response['errors']['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $response['errors']['phone'] = 'Phone number must start with 09 and be exactly 11 digits';
    }
    if (empty($payment)) {
        $response['errors']['payment'] = 'Payment method is required';
    }
    if (empty($delivery)) {
        $response['errors']['delivery'] = 'Delivery option is required';
    }
    if ($delivery === 'delivery' && empty($address)) {
        $response['errors']['address'] = 'Address is required for home delivery';
    }

    // Success response
    if (empty($response['errors'])) {
        $orderNumber = 'RJ' . rand(100000, 999999);
        $response['success'] = true;
        $response['message'] = 'Order placed successfully';
        $response['order_number'] = $orderNumber;
        $response['order_details'] = array(
            'name' => $name,
            'phone' => $phone,
            'payment' => $payment,
            'delivery' => $delivery,
            'address' => $address,
            'cart_items' => $cartItemsArr,
            'order_total' => $orderTotal
        );
    } else {
        $response['message'] = 'Please correct the errors in your form';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


header('Location: ../html/order.html');
exit;
?>
