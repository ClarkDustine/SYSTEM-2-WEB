<?php

include('../config/function.php');

function generatePosOrderReference($prefix)
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}


if(!isset($_SESSION['productItems'])){
      $_SESSION['productItems'] = [];
}

if(!isset($_SESSION['productItemIds'])){
      $_SESSION['productItemIds'] = [];
}

if (isset($_POST['addItem'])) {
    $productId = validate($_POST['product_id']);
    $quantity = validate($_POST['quantity']);

    $checkProduct = mysqli_query($conn, "SELECT * FROM products WHERE id='$productId' LIMIT 1");
    if ($checkProduct) {
        if (mysqli_num_rows($checkProduct) > 0) {
            $row = mysqli_fetch_assoc($checkProduct);
            if ($row['quantity'] < $quantity) {
                redirect('order-create.php', 'Only' . $row['quantity'] . 'quantity available!');
            }

            $productData = [
                'product_id' => $row['id'],
                'name' => $row['name'],
                'image' => $row['image'],
                'price' => $row['price'],
                'quantity' => $quantity,
            ];
            if (!in_array($row['id'], $_SESSION['productItemIds'])) {

                array_push($_SESSION['productItemIds'], $row['id']);
                array_push($_SESSION['productItems'], $productData);

            } else {
                foreach ($_SESSION['productItems'] as $key => $productSessionItem) {
                    if ($productSessionItem['product_id'] == $row['id']) {

                        $newQuantity = $productSessionItem['quantity'] + $quantity;

                        $productData = [
                            'product_id' => $row['id'],
                            'name' => $row['name'],
                            'image' => $row['image'],
                            'price' => $row['price'],
                            'quantity' => $newQuantity,
                        ];
                        $_SESSION['productItems'][$key] = $productData;

                    }
                }
            }
            redirect('order-create.php', 'Item Added.'.$row['name']);

        } else {
            redirect('order-create.php', 'Product Does Not Exist.');

        }

    } else {
        redirect('order-create.php', 'Something Went Wrong!');
    }
}
if(isset($_POST['productIncDec']))
{
       $productId = validate($_POST['product_id']);
       $quantity = validate($_POST['quantity']);

       $flag = false;
       foreach($_SESSION['productItems'] as $key => $item){
        if($item['product_id'] == $productId){

        $flag = true;
        $_SESSION['productItems'][$key]['quantity'] = $quantity;
        }
       }

       if($flag){

           jsonResponse(200, 'success', 'Quantity Updated');
        }else
       {

        jsonResponse(500, 'error', 'Something Went Wrong. Please re-fresh');
       }
}

if(isset($_POST['proceedToPlaceBtn']))
{
    $phone = trim((string)($_POST['cphone'] ?? ''));
    $payment_mode = trim((string)($_POST['payment_mode'] ?? ''));

    if (!in_array($payment_mode, ['Cash Payment', 'Online Payment'], true)) {
        jsonResponse(422, 'warning', 'Select a valid payment mode.');
        exit;
    }
    if ($phone === '' && $payment_mode === 'Online Payment') {
        jsonResponse(422, 'warning', 'A mobile number is required for online payment.');
        exit;
    }
    if ($phone !== '' && !preg_match('/^[0-9+().\s-]{7,30}$/', $phone)) {
        jsonResponse(422, 'warning', 'Enter a valid customer phone number.');
        exit;
    }

    $checkCustomer = mysqli_prepare($conn, 'SELECT id FROM customers WHERE phone = ? LIMIT 1');
    if (!$checkCustomer) {
        jsonResponse(500, 'error', 'Customer details could not be checked. Please try again.');
        exit;
    }
    mysqli_stmt_bind_param($checkCustomer, 's', $phone);
    if (!mysqli_stmt_execute($checkCustomer)) {
        mysqli_stmt_close($checkCustomer);
        jsonResponse(500, 'error', 'Customer details could not be checked. Please try again.');
        exit;
    }
    $customerId = null;
    mysqli_stmt_bind_result($checkCustomer, $customerId);
    mysqli_stmt_fetch($checkCustomer);
    $customerFound = $customerId !== null;
    mysqli_stmt_close($checkCustomer);

    $_SESSION['cphone'] = $phone;
    $_SESSION['payment_mode'] = $payment_mode;
    unset($_SESSION['guest_order'], $_SESSION['customer_id']);
    if ($customerFound) {
        $_SESSION['customer_id'] = (int)$customerId;
        $_SESSION['invoice_no'] = generatePosOrderReference('INV');
        $_SESSION['tracking_no'] = generatePosOrderReference('ORD');
        jsonResponse(200, 'success', 'Customer found.');
    } else {
        unset($_SESSION['invoice_no']);
        unset($_SESSION['tracking_no']);
        jsonResponse(404, 'warning', 'This number is not registered yet.');
    }
    exit;
}

if (isset($_POST['continueAsGuestBtn'])) {
    $payment_mode = trim((string)($_POST['payment_mode'] ?? ''));
    $guestPhone = trim((string)($_POST['cphone'] ?? ''));
    if (empty($_SESSION['productItems'])) {
        jsonResponse(422, 'warning', 'Add at least one product before continuing.');
        exit;
    }
    if (!in_array($payment_mode, ['Cash Payment', 'Online Payment'], true)) {
        jsonResponse(422, 'warning', 'Select a valid payment mode.');
        exit;
    }
    if (($guestPhone === '' && $payment_mode === 'Online Payment') ||
        ($guestPhone !== '' && !preg_match('/^[0-9+().\s-]{7,30}$/', $guestPhone))) {
        jsonResponse(422, 'warning', $payment_mode === 'Online Payment'
            ? 'A mobile number is required for online payment.'
            : 'Enter a valid customer phone number.');
        exit;
    }

    $_SESSION['guest_order'] = true;
    $_SESSION['payment_mode'] = $payment_mode;
    $_SESSION['invoice_no'] = generatePosOrderReference('INV');
    $_SESSION['tracking_no'] = generatePosOrderReference('ORD');
    unset($_SESSION['cphone'], $_SESSION['customer_id']);

    jsonResponse(200, 'success', 'Walk-in order is ready.');
    exit;
}

if(isset($_POST['saveCustomerBtn']))
{
    $name = trim((string)($_POST['name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $payment_mode = trim((string)($_POST['payment_mode'] ?? ($_SESSION['payment_mode'] ?? '')));
    $phone_mode = trim((string)($_POST['phone_mode'] ?? 'existing'));
    $pendingPhone = trim((string)($_SESSION['cphone'] ?? ''));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 150 ||
        !preg_match('/^[0-9+().\s-]{7,30}$/', $phone) ||
        strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(422, 'warning', 'Enter a valid name and email address, and use the contact number entered for this order.');
        exit;
    }
    if (!in_array($phone_mode, ['existing', 'new'], true) ||
        ($phone_mode === 'existing' && ($pendingPhone === '' || $phone !== $pendingPhone))) {
        jsonResponse(422, 'warning', 'Use the contact number entered before starting this order.');
        exit;
    }
    if (!in_array($payment_mode, ['Cash Payment', 'Online Payment'], true)) {
        jsonResponse(422, 'warning', 'Select a payment mode before saving the customer.');
        exit;
    }
    if (empty($_SESSION['productItems'])) {
        jsonResponse(422, 'warning', 'Add at least one product before saving customer details.');
        exit;
    }

    $phoneCheck = mysqli_prepare($conn, 'SELECT id FROM customers WHERE phone = ? LIMIT 1');
    if (!$phoneCheck) {
        jsonResponse(500, 'error', 'Customer details could not be checked. Please try again.');
        exit;
    }
    mysqli_stmt_bind_param($phoneCheck, 's', $phone);
    mysqli_stmt_execute($phoneCheck);
    $phoneCustomerId = null;
    mysqli_stmt_bind_result($phoneCheck, $phoneCustomerId);
    mysqli_stmt_fetch($phoneCheck);
    $phoneAlreadyRegistered = $phoneCustomerId !== null;
    mysqli_stmt_close($phoneCheck);

    if ($phoneAlreadyRegistered) {
        $_SESSION['cphone'] = $phone;
        $_SESSION['customer_id'] = (int)$phoneCustomerId;
        $_SESSION['payment_mode'] = $payment_mode;
        $_SESSION['invoice_no'] = generatePosOrderReference('INV');
        $_SESSION['tracking_no'] = generatePosOrderReference('ORD');
        unset($_SESSION['guest_order']);
        jsonResponse(200, 'success', 'This contact is already registered. Continuing with the order.');
        exit;
    }

    $emailCheck = mysqli_prepare($conn, 'SELECT id FROM customers WHERE email = ? LIMIT 1');
    if (!$emailCheck) {
        jsonResponse(500, 'error', 'Customer details could not be checked. Please try again.');
        exit;
    }
    mysqli_stmt_bind_param($emailCheck, 's', $email);
    mysqli_stmt_execute($emailCheck);
    $emailCustomerId = null;
    mysqli_stmt_bind_result($emailCheck, $emailCustomerId);
    mysqli_stmt_fetch($emailCheck);
    $emailAlreadyRegistered = $emailCustomerId !== null;
    mysqli_stmt_close($emailCheck);
    if ($emailAlreadyRegistered) {
        $_SESSION['cphone'] = $phone;
        $_SESSION['customer_id'] = (int)$emailCustomerId;
        $_SESSION['payment_mode'] = $payment_mode;
        $_SESSION['invoice_no'] = generatePosOrderReference('INV');
        $_SESSION['tracking_no'] = generatePosOrderReference('ORD');
        unset($_SESSION['guest_order']);
        jsonResponse(200, 'success', 'Using the existing customer record for this order.');
        exit;
    }

    $saveCustomer = mysqli_prepare($conn, 'INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)');
    if (!$saveCustomer) {
        jsonResponse(500, 'error', 'Customer could not be saved. Please try again.');
        exit;
    }
    mysqli_stmt_bind_param($saveCustomer, 'sss', $name, $email, $phone);
    $saved = mysqli_stmt_execute($saveCustomer);
    $newCustomerId = $saved ? (int)mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($saveCustomer);

    if (!$saved) {
        jsonResponse(500, 'error', 'Customer could not be saved. Please try again.');
        exit;
    }

    $_SESSION['cphone'] = $phone;
    $_SESSION['customer_id'] = $newCustomerId;
    $_SESSION['payment_mode'] = $payment_mode;
    $_SESSION['invoice_no'] = generatePosOrderReference('INV');
    $_SESSION['tracking_no'] = generatePosOrderReference('ORD');
    unset($_SESSION['guest_order']);
    jsonResponse(200, 'success', 'Customer saved. Continuing with the order.');
    exit;
}

if(isset($_POST['placeOrder']))
{
    if(!isset($_SESSION['productItems']) || empty($_SESSION['productItems'])){
        redirect('order-create.php', 'No order items found.');
    }

    $phone = isset($_SESSION['cphone']) ? validate($_SESSION['cphone']) : '';
    $sessionCustomerId = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;
    $payment_mode = isset($_SESSION['payment_mode']) ? validate($_SESSION['payment_mode']) : '';
    $invoice_no = isset($_SESSION['invoice_no']) ? validate($_SESSION['invoice_no']) : '';
    $tracking_no = isset($_SESSION['tracking_no']) ? validate($_SESSION['tracking_no']) : '';
    $isGuestOrder = !empty($_SESSION['guest_order']) && $phone === '' && $sessionCustomerId === 0;

    if(($phone == '' && !$isGuestOrder && $sessionCustomerId === 0) || $payment_mode == '' || $invoice_no == '' || $tracking_no == ''){
        redirect('order-create.php', 'Incomplete order details.');
    }

    $customer = null;
    if ($sessionCustomerId > 0) {
        $customerQuery = mysqli_query($conn, "SELECT * FROM customers WHERE id='$sessionCustomerId' LIMIT 1");
        if(!$customerQuery || mysqli_num_rows($customerQuery) == 0){
            redirect('order-create.php', 'Customer not found.');
        }
        $customer = mysqli_fetch_assoc($customerQuery);
    } elseif ($phone !== '') {
        $customerQuery = mysqli_query($conn, "SELECT * FROM customers WHERE phone='$phone' LIMIT 1");
        if(!$customerQuery || mysqli_num_rows($customerQuery) == 0){
            redirect('order-create.php', 'Customer not found.');
        }
        $customer = mysqli_fetch_assoc($customerQuery);
    }

    $grandTotal = 0;
    foreach($_SESSION['productItems'] as $item){
        $grandTotal += $item['price'] * $item['quantity'];
    }

    $orderDate = date('Y-m-d');
    if ($customer) {
        $orderStatement = mysqli_prepare($conn, "INSERT INTO orders (customer_id, tracking_no, invoice_no, total_amount, order_date, order_status, payment_mode, order_placed_by_id) VALUES (?, ?, ?, ?, ?, 'pending', ?, 0)");
        if ($orderStatement) {
            $customerId = (int)$customer['id'];
            mysqli_stmt_bind_param($orderStatement, 'issdss', $customerId, $tracking_no, $invoice_no, $grandTotal, $orderDate, $payment_mode);
        }
    } else {
        $orderStatement = mysqli_prepare($conn, "INSERT INTO orders (customer_id, tracking_no, invoice_no, total_amount, order_date, order_status, payment_mode, order_placed_by_id) VALUES (NULL, ?, ?, ?, ?, 'pending', ?, 0)");
        if ($orderStatement) {
            mysqli_stmt_bind_param($orderStatement, 'ssdss', $tracking_no, $invoice_no, $grandTotal, $orderDate, $payment_mode);
        }
    }
    $orderCreated = $orderStatement ? mysqli_stmt_execute($orderStatement) : false;
    if ($orderStatement) {
        mysqli_stmt_close($orderStatement);
    }
    if($orderCreated){
        $orderId = mysqli_insert_id($conn);
        foreach($_SESSION['productItems'] as $item){
            $itemData = [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'price' => $item['price'],
                'quantity' => $item['quantity']
            ];
            insert('order_items', $itemData);
        }
        unset($_SESSION['productItems']);
        unset($_SESSION['productItemIds']);
        unset($_SESSION['invoice_no']);
        unset($_SESSION['tracking_no']);
        unset($_SESSION['cphone']);
        unset($_SESSION['customer_id']);
        unset($_SESSION['payment_mode']);
        unset($_SESSION['guest_order']);

        redirect('orders.php', 'Order placed successfully. Order Code: ' . $tracking_no);
    } else {
        redirect('order-summary.php', 'Something went wrong while saving the order.');
    }
}

if(isset($_POST['updateOrderStatus']))
{
    $orderId = validate($_POST['order_id']);
    $status = validate($_POST['updateOrderStatus']);
    $allowed = ['completed', 'cancelled'];

    if($orderId == '' || !in_array($status, $allowed)){
        redirect('orders.php', 'Invalid order status update.');
    }

    $orderQuery = mysqli_query($conn, "SELECT order_status FROM orders WHERE id='$orderId' LIMIT 1");
    $currentStatus = '';
    if($orderQuery && mysqli_num_rows($orderQuery) > 0){
        $orderRow = mysqli_fetch_assoc($orderQuery);
        $currentStatus = $orderRow['order_status'];
    }

    $result = update('orders', $orderId, ['order_status' => $status]);
    if($result){
        if($status === 'completed' && $currentStatus !== 'completed'){
            $itemsQuery = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id='$orderId'");
            if($itemsQuery){
                while($item = mysqli_fetch_assoc($itemsQuery)){
                    $productId = validate($item['product_id']);
                    $orderQty = validate($item['quantity']);
                    mysqli_query($conn, "UPDATE products SET quantity = GREATEST(quantity - $orderQty, 0) WHERE id='$productId'");
                }
            }
        }
        redirect('orders.php', 'Order status updated to '. ucfirst($status) .'.');
    } else {
        redirect('orders.php', 'Unable to update order status.');
    }
}

?>
