<?php
$appDirectory = 'Online-Point-Sale-and-Inventory-Management-System-for-The-Valley';
$appUrl = '/' . rawurlencode($appDirectory) . '/';
$customerOrderUrl = $appUrl . 'order-product.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . DIRECTORY_SEPARATOR . $appDirectory . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'dbcon.php';
mysqli_set_charset($conn, 'utf8mb4');

if (!isset($_SESSION['customer_order_csrf'])) {
    $_SESSION['customer_order_csrf'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['customer_order_cart']) || !is_array($_SESSION['customer_order_cart'])) {
    $_SESSION['customer_order_cart'] = [];
}

function customerOrderEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function customerOrderProductImage(string $appUrl, ?string $image): ?string
{
    $image = str_replace('\\', '/', trim((string) $image));
    if (!preg_match('#^assets/uploads/products/[A-Za-z0-9._-]+$#', $image)) {
        return null;
    }

    return $appUrl . implode('/', array_map('rawurlencode', explode('/', $image)));
}

function customerOrderFlashRedirect(string $type, string $message, string $section = 'cart'): void
{
    global $customerOrderUrl;
    $_SESSION['customer_order_notice'] = ['type' => $type, 'message' => $message];
    header('Location: ' . $customerOrderUrl . '#' . $section);
    exit;
}

$formValues = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'payment_mode' => 'cash',
    'fulfillment_type' => 'pickup',
    'delivery_address' => '',
];
$notice = $_SESSION['customer_order_notice'] ?? null;
unset($_SESSION['customer_order_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['name'] = trim((string) ($_POST['name'] ?? ''));
    $formValues['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $formValues['email'] = trim((string) ($_POST['email'] ?? ''));
    $formValues['payment_mode'] = (string) ($_POST['payment_mode'] ?? 'cash');
    $formValues['fulfillment_type'] = (string) ($_POST['fulfillment_type'] ?? 'pickup');
    $formValues['delivery_address'] = trim((string) ($_POST['delivery_address'] ?? ''));

    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['customer_order_csrf'], $postedToken)) {
        $notice = ['type' => 'danger', 'message' => 'Your session expired. Refresh the page and try again.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $cart = $_SESSION['customer_order_cart'];

        if ($action === 'add_to_cart') {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if (!$productId || !$quantity) {
                customerOrderFlashRedirect('danger', 'Choose a valid product quantity.', 'products');
            }

            $productStmt = mysqli_prepare($conn, "SELECT p.id, p.quantity FROM products p WHERE p.id = ? AND p.status = 0 LIMIT 1");
            mysqli_stmt_bind_param($productStmt, 'i', $productId);
            mysqli_stmt_execute($productStmt);
            $productResult = mysqli_stmt_get_result($productStmt);
            $product = $productResult ? mysqli_fetch_assoc($productResult) : null;
            mysqli_stmt_close($productStmt);

            if (!$product) {
                customerOrderFlashRedirect('warning', 'That product is no longer available.', 'products');
            }

            $newQuantity = (int) ($cart[$productId] ?? 0) + $quantity;
            if ((int) $product['quantity'] < $newQuantity) {
                $availableForAdd = max(0, (int) $product['quantity'] - (int) ($cart[$productId] ?? 0));
                customerOrderFlashRedirect('warning', 'Sorry, only ' . $availableForAdd . ' stock available.', 'products');
            }

            $cart[$productId] = $newQuantity;
            $_SESSION['customer_order_cart'] = $cart;
            customerOrderFlashRedirect('success', 'Added to your order.', 'products');
        }

        if (isset($_POST['remove_item'])) {
            $productId = (int) $_POST['remove_item'];
            unset($cart[$productId]);
            $_SESSION['customer_order_cart'] = $cart;
            customerOrderFlashRedirect('success', 'Item removed from your order.');
        }

        if ($action === 'update_cart') {
            $quantities = $_POST['quantities'] ?? [];
            if (!is_array($quantities)) {
                customerOrderFlashRedirect('danger', 'The cart could not be updated.');
            }

            foreach ($quantities as $id => $requestedQuantity) {
                $productId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $quantity = filter_var($requestedQuantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
                if (!$productId || !$quantity || !isset($cart[$productId])) {
                    customerOrderFlashRedirect('danger', 'Enter a valid quantity for each item.');
                }

                $productStmt = mysqli_prepare($conn, "SELECT p.quantity FROM products p WHERE p.id = ? AND p.status = 0 LIMIT 1");
                mysqli_stmt_bind_param($productStmt, 'i', $productId);
                mysqli_stmt_execute($productStmt);
                $productResult = mysqli_stmt_get_result($productStmt);
                $product = $productResult ? mysqli_fetch_assoc($productResult) : null;
                mysqli_stmt_close($productStmt);

                if (!$product || (int) $product['quantity'] < $quantity) {
                    customerOrderFlashRedirect('warning', 'One or more quantities exceed the available stock.');
                }
                $cart[$productId] = $quantity;
            }

            $_SESSION['customer_order_cart'] = $cart;
            customerOrderFlashRedirect('success', 'Your order quantities were updated.');
        }

        if ($action === 'place_order') {
            $errors = [];
            if (mb_strlen($formValues['name']) < 2 || mb_strlen($formValues['name']) > 150) {
                $errors[] = 'Enter your name (2 to 150 characters).';
            }
            if (!preg_match('/^[0-9+() .-]{7,30}$/', $formValues['phone'])) {
                $errors[] = 'Enter a valid phone number.';
            }
            if (mb_strlen($formValues['email']) > 150) {
                $errors[] = 'Email must be 150 characters or fewer.';
            }
            if ($formValues['payment_mode'] !== 'cash') {
                $errors[] = 'Cash is the available payment method right now.';
            }
            if (!in_array($formValues['fulfillment_type'], ['pickup', 'delivery'], true)) {
                $errors[] = 'Choose pickup or delivery.';
            } elseif ($formValues['fulfillment_type'] === 'delivery') {
                if (mb_strlen($formValues['delivery_address']) < 8 || mb_strlen($formValues['delivery_address']) > 1000) {
                    $errors[] = 'Enter a complete delivery address (8 to 1,000 characters).';
                }
            } else {
                $formValues['delivery_address'] = '';
            }
            if (!$cart) {
                $errors[] = 'Add at least one product to your order.';
            }

            if (!$errors) {
                $productIds = array_values(array_filter(array_map('intval', array_keys($cart)), static fn($id) => $id > 0));
                $idList = implode(',', $productIds);
                mysqli_begin_transaction($conn);

                try {
                    $productsResult = mysqli_query($conn, "SELECT p.id, p.name, p.price, p.quantity FROM products p WHERE p.id IN ($idList) AND p.status = 0 FOR UPDATE");
                    if (!$productsResult) {
                        throw new RuntimeException('Products could not be checked. Please try again.');
                    }

                    $orderedProducts = [];
                    while ($product = mysqli_fetch_assoc($productsResult)) {
                        $orderedProducts[(int) $product['id']] = $product;
                    }
                    if (count($orderedProducts) !== count($productIds)) {
                        throw new RuntimeException('A product in your cart is no longer available. Review your order and try again.');
                    }

                    $total = 0.0;
                    foreach ($cart as $productId => $quantity) {
                        $productId = (int) $productId;
                        $quantity = (int) $quantity;
                        if (!isset($orderedProducts[$productId]) || $quantity < 1 || $quantity > (int) $orderedProducts[$productId]['quantity']) {
                            throw new RuntimeException('One or more items do not have enough stock. Review your order and try again.');
                        }
                        $total += (float) $orderedProducts[$productId]['price'] * $quantity;
                    }

                    $customerId = 0;
                    $findCustomer = mysqli_prepare($conn, 'SELECT id FROM customers WHERE phone = ? LIMIT 1');
                    mysqli_stmt_bind_param($findCustomer, 's', $formValues['phone']);
                    mysqli_stmt_execute($findCustomer);
                    $customerResult = mysqli_stmt_get_result($findCustomer);
                    $existingCustomer = $customerResult ? mysqli_fetch_assoc($customerResult) : null;
                    mysqli_stmt_close($findCustomer);

                    $email = $formValues['email'] !== '' ? $formValues['email'] : null;
                    if ($email !== null) {
                        $checkEmail = mysqli_prepare($conn, 'SELECT id FROM customers WHERE email = ? LIMIT 1');
                        mysqli_stmt_bind_param($checkEmail, 's', $email);
                        mysqli_stmt_execute($checkEmail);
                        $emailResult = mysqli_stmt_get_result($checkEmail);
                        $emailCustomer = $emailResult ? mysqli_fetch_assoc($emailResult) : null;
                        mysqli_stmt_close($checkEmail);

                        if ($emailCustomer && (!$existingCustomer || (int) $emailCustomer['id'] !== (int) $existingCustomer['id'])) {
                            throw new RuntimeException('That email is already on a different customer record. Please use another email or contact the shop.');
                        }
                    }

                    if ($existingCustomer) {
                        $customerId = (int) $existingCustomer['id'];
                        if ($email !== null) {
                            $saveCustomer = mysqli_prepare($conn, 'UPDATE customers SET name = ?, email = ? WHERE id = ?');
                            mysqli_stmt_bind_param($saveCustomer, 'ssi', $formValues['name'], $email, $customerId);
                        } else {
                            $saveCustomer = mysqli_prepare($conn, 'UPDATE customers SET name = ? WHERE id = ?');
                            mysqli_stmt_bind_param($saveCustomer, 'si', $formValues['name'], $customerId);
                        }
                        if (!mysqli_stmt_execute($saveCustomer)) {
                            mysqli_stmt_close($saveCustomer);
                            throw new RuntimeException('Your contact details could not be saved. Please try again.');
                        }
                        mysqli_stmt_close($saveCustomer);
                    } else {
                        $saveCustomer = mysqli_prepare($conn, 'INSERT INTO customers (name, email, phone, status) VALUES (?, ?, ?, 0)');
                        mysqli_stmt_bind_param($saveCustomer, 'sss', $formValues['name'], $email, $formValues['phone']);
                        if (!mysqli_stmt_execute($saveCustomer)) {
                            mysqli_stmt_close($saveCustomer);
                            throw new RuntimeException('Your contact details could not be saved. Please try again.');
                        }
                        $customerId = (int) mysqli_insert_id($conn);
                        mysqli_stmt_close($saveCustomer);
                    }

                    $invoiceNo = 'WEB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                    $trackingNo = 'WEB-TRK-' . strtoupper(bin2hex(random_bytes(5)));
                    $fulfillmentType = $formValues['fulfillment_type'];
                    $paymentMode = $fulfillmentType === 'delivery' ? 'Cash on Delivery' : 'Cash on Pickup';
                    $deliveryAddress = $fulfillmentType === 'delivery' ? $formValues['delivery_address'] : null;
                    $saveOrder = mysqli_prepare($conn, "INSERT INTO orders (customer_id, tracking_no, invoice_no, total_amount, order_date, order_status, payment_mode, order_placed_by_id, fulfillment_type, delivery_address) VALUES (?, ?, ?, ?, CURDATE(), 'pending', ?, 0, ?, ?)");
                    mysqli_stmt_bind_param($saveOrder, 'issdsss', $customerId, $trackingNo, $invoiceNo, $total, $paymentMode, $fulfillmentType, $deliveryAddress);
                    if (!mysqli_stmt_execute($saveOrder)) {
                        mysqli_stmt_close($saveOrder);
                        throw new RuntimeException('Your order could not be saved. Please try again.');
                    }
                    $orderId = (int) mysqli_insert_id($conn);
                    mysqli_stmt_close($saveOrder);

                    $saveItem = mysqli_prepare($conn, 'INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)');
                    foreach ($cart as $productId => $quantity) {
                        $productId = (int) $productId;
                        $quantity = (int) $quantity;
                        $price = (float) $orderedProducts[$productId]['price'];
                        mysqli_stmt_bind_param($saveItem, 'iidi', $orderId, $productId, $price, $quantity);
                        if (!mysqli_stmt_execute($saveItem)) {
                            mysqli_stmt_close($saveItem);
                            throw new RuntimeException('An item could not be added to your order. Please try again.');
                        }
                    }
                    mysqli_stmt_close($saveItem);
                    mysqli_commit($conn);

                    $receiptItems = [];
                    foreach ($cart as $productId => $quantity) {
                        $productId = (int) $productId;
                        $receiptItems[] = [
                            'name' => (string) $orderedProducts[$productId]['name'],
                            'price' => (float) $orderedProducts[$productId]['price'],
                            'quantity' => (int) $quantity,
                        ];
                    }
                    $_SESSION['customer_order_receipt'] = [
                        'tracking_no' => $trackingNo,
                        'invoice_no' => $invoiceNo,
                        'created_at' => date('M d, Y h:i A'),
                        'name' => $formValues['name'],
                        'phone' => $formValues['phone'],
                        'email' => $email ?? '',
                        'fulfillment_type' => $fulfillmentType,
                        'delivery_address' => $deliveryAddress ?? '',
                        'payment_mode' => $paymentMode,
                        'total' => $total,
                        'items' => $receiptItems,
                    ];
                    $_SESSION['customer_order_cart'] = [];
                    customerOrderFlashRedirect('success', 'Pre-order received! Your reference is ' . $trackingNo . '. Please wait for the shop to confirm it.', 'receipt');
                } catch (Throwable $exception) {
                    mysqli_rollback($conn);
                    $errors[] = $exception->getMessage();
                }
            }

            if ($errors) {
                $notice = ['type' => 'danger', 'message' => implode(' ', $errors)];
            }
        }
    }
}

$productQuery = mysqli_query($conn, "SELECT p.id, p.category_id, p.name, p.description, p.price, p.quantity, p.image, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 0 ORDER BY c.name ASC, p.name ASC");
$products = [];
if ($productQuery) {
    while ($product = mysqli_fetch_assoc($productQuery)) {
        $products[(int) $product['id']] = $product;
    }
}

foreach (array_keys($_SESSION['customer_order_cart']) as $cartId) {
    if (!isset($products[(int) $cartId])) {
        unset($_SESSION['customer_order_cart'][$cartId]);
        if (!$notice) {
            $notice = ['type' => 'warning', 'message' => 'A product in your cart is no longer available and was removed.'];
        }
    }
}

$cartProducts = [];
$cartTotal = 0.0;
foreach ($_SESSION['customer_order_cart'] as $productId => $quantity) {
    $productId = (int) $productId;
    if (isset($products[$productId])) {
        $product = $products[$productId];
        $product['cart_quantity'] = (int) $quantity;
        $product['line_total'] = (float) $product['price'] * (int) $quantity;
        $cartProducts[] = $product;
        $cartTotal += $product['line_total'];
    }
}
$csrfToken = $_SESSION['customer_order_csrf'];
$receipt = $_SESSION['customer_order_receipt'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Product | Sweet Valley The Coffee</title>
    <meta name="description" content="Browse the Sweet Valley menu and place a pickup or delivery pre-order.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        :root { --espresso: #2b1a10; --seal: #6a4127; --kraft: #c9a37a; --sand: #f3e8d7; --paper: #fbf6ec; --ink: #352116; --muted: #806b59; }
        body { min-height: 100vh; background: linear-gradient(rgba(251,246,236,.84), rgba(251,246,236,.9)), url('<?= customerOrderEscape($appUrl . 'admin/assets/img/BeigeBG.png') ?>') center / cover fixed; color: var(--ink); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        h1, h2, h3 { color: var(--espresso); font-family: Georgia, "Times New Roman", serif; font-weight: 700; }
        .topbar { background: var(--sand); border-bottom: 1px solid rgba(106,65,39,.22); }
        .brand { display: inline-flex; align-items: center; text-decoration: none; }
        .brand-logo { display: block; width: 32px !important; height: 32px !important; max-width: 32px; max-height: 32px; object-fit: contain; flex: 0 0 32px; }
        .brand-name { color: var(--espresso); font-family: Georgia, "Times New Roman", serif; font-size: 1.1rem; font-weight: 700; white-space: nowrap; }
        .btn-coffee { background: var(--seal); border-color: var(--seal); color: #fffaf1; font-weight: 600; }
        .btn-coffee:hover { background: var(--espresso); border-color: var(--espresso); color: #fff; }
        .btn-outline-coffee { color: var(--seal); border-color: var(--seal); }
        .btn-outline-coffee:hover { background: var(--seal); color: #fffaf1; }
        .intro-panel, .shop-panel { border: 1px solid rgba(106,65,39,.2); border-radius: .5rem; background: rgba(255,250,241,.95); box-shadow: 0 10px 28px rgba(53,33,22,.08); }
        .intro-panel { padding: clamp(1.5rem,4vw,3rem); }
        .eyebrow { color: var(--seal); font-size: .78rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }
        .product-card { height: 100%; overflow: hidden; border: 1px solid rgba(106,65,39,.16); border-radius: .45rem; background: #fffdf8; }
        .product-photo { display: grid; height: 220px; place-items: center; overflow: hidden; background: #eee0cb; color: var(--muted); font-size: .9rem; }
        .product-photo img { width: 100%; height: 100%; object-fit: cover; }
        .product-card .card-body { display: flex; flex-direction: column; padding: 1rem 1rem 1.1rem; }
        .product-card h3 { line-height: 1.25; overflow-wrap: anywhere; }
        .product-description { line-height: 1.5; }
        .price { color: var(--seal); font-size: 1.1rem; font-weight: 700; }
        .stock-label { color: var(--muted); font-size: .85rem; }
        .shop-panel { padding: clamp(1.1rem, 3vw, 2rem); }
        #products { scroll-margin-top: 90px; }
        .form-control, .form-select { border-color: #ddc5a4; }
        .customer-notice-overlay { position: fixed; inset: 0; z-index: 1080; display: grid; place-items: center; padding: 1rem; background: rgba(29,17,10,.5); backdrop-filter: blur(3px); }
        .order-notice { width: min(100%, 29rem); margin: 0; border: 1px solid rgba(106,65,39,.2); border-left: 6px solid var(--seal); border-radius: 1rem; background: #fffaf1; color: var(--ink); padding: 1.5rem; box-shadow: 0 24px 60px rgba(29,17,10,.3); }
        .order-notice.alert-success { border-left-color: #3f8051; background: #edf6ee; }
        .order-notice.alert-danger { border-left-color: #a43f36; background: #fff0ee; }
        .order-notice.alert-warning { border-left-color: #ad7518; background: #fff8e8; }
        .order-notice .notice-message { margin: 0; font-size: 1.05rem; line-height: 1.5; }
        .form-control:focus, .form-select:focus { border-color: #a66a3f; box-shadow: 0 0 0 .2rem rgba(166,106,63,.14); }
        footer { background: #1d110a; color: #c9b79e; }
        footer a { color: #f3e8d7; }
        @media (max-width: 575.98px) {
            .topbar .container { flex-wrap: wrap; row-gap: .6rem; }
            .topbar-actions { width: 100%; justify-content: flex-end; }
            .topbar-actions .btn { flex: 1 1 0; text-align: center; white-space: nowrap; }
            main.container { padding-left: .85rem; padding-right: .85rem; }
            .intro-panel { padding: 1.15rem; }
            .intro-panel .display-5 { font-size: clamp(2.1rem, 9vw, 2.8rem); }
            #products > .d-flex h2 { font-size: 1.55rem; }
            #products > .d-flex > a { padding: .4rem .6rem; font-size: .85rem; }
            .product-photo { height: 190px; }
            .product-card .card-body { padding: .9rem; }
            .product-card form .form-control { min-width: 0; }
            .shop-panel { padding: 1rem; }
            #cart .table { font-size: .88rem; }
        }
        @media print {
            body * { visibility: hidden !important; }
            #receipt, #receipt * { visibility: visible !important; }
            #receipt { position: absolute; inset: 0 auto auto 0; width: 100%; margin: 0 !important; border: 0; box-shadow: none; background: #fff; }
            #receipt .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<header class="topbar sticky-top">
    <div class="container d-flex align-items-center justify-content-between py-3 gap-3">
        <a class="brand" href="./" aria-label="Sweet Valley The Coffee home"><img class="brand-logo" src="<?= customerOrderEscape($appUrl . 'admin/assets/img/removedbackgroundlogo.png') ?>" alt="" width="32" height="32"><span class="brand-name">The Valley</span></a>
        <div class="d-flex gap-2 topbar-actions">
            <a class="btn btn-outline-coffee" href="./">Home</a>
            <a class="btn btn-coffee" href="<?= customerOrderEscape($appUrl . 'login.php') ?>">Staff login</a>
        </div>
    </div>
</header>

<main class="container py-4 py-lg-5">
    <section class="intro-panel mb-4 mb-lg-5">
        <div class="row align-items-center g-4">
            <div class="col-12">
                <span class="eyebrow">Sweet Valley online shop</span>
                <h1 class="display-5 mt-2 mb-3">Pick your favorites. We’ll get them ready.</h1>
                <p class="lead mb-0">Browse the current shop menu, add what you’d like, and send us your contact details. Your pre-order will appear in the staff order list for confirmation.</p>
            </div>
        </div>
    </section>

    <?php if ($notice): ?>
        <div class="customer-notice-overlay" id="customerNoticeOverlay">
            <div class="alert alert-<?= customerOrderEscape($notice['type']) ?> order-notice" role="alertdialog" aria-modal="true" aria-label="Order notification">
                <p class="notice-message"><?= customerOrderEscape($notice['message']) ?></p>
                <div class="d-flex justify-content-end mt-3"><button class="btn btn-coffee" type="button" data-dismiss-notice>OK</button></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (is_array($receipt)): ?>
        <section id="receipt" class="shop-panel mb-4" aria-labelledby="receiptTitle">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                    <span class="eyebrow">Pre-order receipt</span>
                    <h2 id="receiptTitle" class="h3 mb-1">Order received</h2>
                    <p class="text-secondary mb-0">Reference: <strong><?= customerOrderEscape($receipt['tracking_no']) ?></strong></p>
                </div>
                <button class="btn btn-outline-coffee no-print" type="button" onclick="window.print()">Print receipt</button>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <div><strong>Invoice:</strong> <?= customerOrderEscape($receipt['invoice_no']) ?></div>
                    <div><strong>Date:</strong> <?= customerOrderEscape($receipt['created_at']) ?></div>
                    <div><strong>Customer:</strong> <?= customerOrderEscape($receipt['name']) ?></div>
                    <div><strong>Phone:</strong> <?= customerOrderEscape($receipt['phone']) ?></div>
                    <?php if ($receipt['email'] !== ''): ?><div><strong>Email:</strong> <?= customerOrderEscape($receipt['email']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <div><strong>Fulfillment:</strong> <?= $receipt['fulfillment_type'] === 'delivery' ? 'Delivery' : 'Pickup' ?></div>
                    <?php if ($receipt['fulfillment_type'] === 'delivery'): ?>
                        <div><strong>Delivery address:</strong><br><?= nl2br(customerOrderEscape($receipt['delivery_address'])) ?></div>
                    <?php endif; ?>
                    <div><strong>Payment:</strong> <?= customerOrderEscape($receipt['payment_mode']) ?></div>
                    <div><strong>Order status:</strong> Pending shop confirmation</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        <?php foreach ($receipt['items'] as $receiptItem): ?>
                            <tr>
                                <td><?= customerOrderEscape($receiptItem['name']) ?></td>
                                <td class="text-end"><?= (int) $receiptItem['quantity'] ?></td>
                                <td class="text-end">₱<?= number_format((float) $receiptItem['price'], 2) ?></td>
                                <td class="text-end">₱<?= number_format((float) $receiptItem['price'] * (int) $receiptItem['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3" class="text-end">Items total</th><th class="text-end">₱<?= number_format((float) $receipt['total'], 2) ?></th></tr></tfoot>
                </table>
            </div>
            <p class="small text-secondary mb-0">This confirms your pre-order only; it is not proof of payment. The shop will contact you to confirm the order<?= $receipt['fulfillment_type'] === 'delivery' ? ' and delivery fee' : '' ?>.</p>
        </section>
    <?php endif; ?>

    <section id="products" class="mb-5">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
            <div>
                <span class="eyebrow">From the menu</span>
                <h2 class="mb-0">Order Product</h2>
            </div>
            <a class="btn btn-outline-coffee" href="#cart">Your order (<?= count($cartProducts) ?>)</a>
        </div>

        <?php if (!$productQuery): ?>
            <div class="alert alert-danger">The menu could not be loaded right now. Please try again later.</div>
        <?php elseif (!$products): ?>
            <div class="shop-panel">The online menu is being prepared. Please check back soon or visit us in store.</div>
        <?php else: ?>
            <div class="row g-3 g-lg-4">
                <?php foreach ($products as $product): ?>
                    <?php $imageUrl = customerOrderProductImage($appUrl, $product['image'] ?? null); ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <article class="product-card">
                            <div class="product-photo">
                                <?php if ($imageUrl): ?>
                                    <img src="<?= customerOrderEscape($imageUrl) ?>" alt="<?= customerOrderEscape($product['name']) ?>">
                                <?php else: ?>
                                    <span>No product photo</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="eyebrow mb-1"><?= customerOrderEscape($product['category_name'] ?: 'Menu') ?></div>
                                <h3 class="h5 mb-2"><?= customerOrderEscape($product['name']) ?></h3>
                                <?php if (trim((string) $product['description']) !== ''): ?>
                                    <p class="product-description text-secondary small mb-3"><?= nl2br(customerOrderEscape($product['description'])) ?></p>
                                <?php else: ?>
                                    <div class="mb-3"></div>
                                <?php endif; ?>
                                <div class="d-flex flex-column align-items-start mt-auto mb-3 gap-1">
                                    <span class="price">₱<?= number_format((float) $product['price'], 2) ?></span>
                                    <?php if ((int) $product['quantity'] > 0): ?>
                                        <span class="stock-label">Stock : <?= number_format(max(0, (int) $product['quantity'])) ?></span>
                                    <?php else: ?>
                                        <span class="stock-label">Stock : 0 <span class="badge text-bg-secondary ms-1">Unavailable</span></span>
                                    <?php endif; ?>
                                </div>
                                <form method="post" action="<?= customerOrderEscape($customerOrderUrl) ?>#products" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= customerOrderEscape($csrfToken) ?>">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input class="form-control" type="number" name="quantity" min="1" value="1" aria-label="Quantity for <?= customerOrderEscape($product['name']) ?>" <?= (int) $product['quantity'] < 1 ? 'disabled' : '' ?>>
                                    <button class="btn btn-coffee text-nowrap" type="submit" <?= (int) $product['quantity'] < 1 ? 'disabled' : '' ?>><?= (int) $product['quantity'] < 1 ? 'Unavailable' : 'Add' ?></button>
                                </form>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="cart" class="row g-4">
        <div class="col-lg-7">
            <div class="shop-panel h-100">
                <span class="eyebrow">Review</span>
                <h2 class="h3 mb-3">Your order</h2>
                <?php if (!$cartProducts): ?>
                    <p class="text-secondary mb-0">Your order is empty. Add something from the menu above to get started.</p>
                <?php else: ?>
                    <form method="post" action="<?= customerOrderEscape($customerOrderUrl) ?>#cart">
                        <input type="hidden" name="csrf_token" value="<?= customerOrderEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="update_cart">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Product</th><th class="text-end">Price</th><th style="width: 110px">Qty</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                    <?php foreach ($cartProducts as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?= customerOrderEscape($product['name']) ?></strong>
                                                <?php if ((int) $product['quantity'] === 0): ?><div class="text-danger small">Currently sold out</div><?php endif; ?>
                                                <button class="btn btn-link btn-sm text-danger p-0" type="submit" name="remove_item" value="<?= (int) $product['id'] ?>">Remove</button>
                                            </td>
                                            <td class="text-end">₱<?= number_format((float) $product['price'], 2) ?></td>
                                            <td><input class="form-control form-control-sm" type="number" name="quantities[<?= (int) $product['id'] ?>]" min="1" max="99" value="<?= (int) $product['cart_quantity'] ?>" aria-label="Quantity for <?= customerOrderEscape($product['name']) ?>"></td>
                                            <td class="text-end">₱<?= number_format((float) $product['line_total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end">₱<?= number_format($cartTotal, 2) ?></th></tr></tfoot>
                            </table>
                        </div>
                        <button class="btn btn-outline-coffee" type="submit">Update quantities</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="shop-panel">
                <span class="eyebrow">Contact details</span>
                <h2 class="h3 mb-3">Delivery and contact details</h2>
                <p class="small text-secondary">We’ll use these details to confirm your order. Your contact will be added to the shop’s customer list.</p>
                <form method="post" action="<?= customerOrderEscape($customerOrderUrl) ?>#cart">
                    <input type="hidden" name="csrf_token" value="<?= customerOrderEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="place_order">
                    <div class="mb-3">
                        <label class="form-label" for="fulfillmentType">Order type *</label>
                        <select class="form-select" id="fulfillmentType" name="fulfillment_type" required>
                            <option value="pickup" <?= $formValues['fulfillment_type'] === 'pickup' ? 'selected' : '' ?>>Pickup</option>
                            <option value="delivery" <?= $formValues['fulfillment_type'] === 'delivery' ? 'selected' : '' ?>>Delivery</option>
                        </select>
                    </div>
                    <div class="mb-3" id="deliveryAddressGroup" <?= $formValues['fulfillment_type'] === 'delivery' ? '' : 'hidden' ?>>
                        <label class="form-label" for="deliveryAddress">Complete delivery address *</label>
                        <textarea class="form-control" id="deliveryAddress" name="delivery_address" rows="3" maxlength="1000" autocomplete="street-address" placeholder="House/building, street, barangay, city/municipality, province" <?= $formValues['fulfillment_type'] === 'delivery' ? 'required' : '' ?>><?= customerOrderEscape($formValues['delivery_address']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="customerName">Name *</label>
                        <input class="form-control" id="customerName" name="name" type="text" maxlength="150" autocomplete="name" required value="<?= customerOrderEscape($formValues['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="customerPhone">Phone number *</label>
                        <input class="form-control" id="customerPhone" name="phone" type="tel" maxlength="30" autocomplete="tel" required value="<?= customerOrderEscape($formValues['phone']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="customerEmail">Email (optional)</label>
                        <input class="form-control" id="customerEmail" name="email" type="text" inputmode="email" autocapitalize="none" spellcheck="false" maxlength="150" autocomplete="email" value="<?= customerOrderEscape($formValues['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="paymentModeLabel">Payment method</label>
                        <div class="form-control bg-light" id="paymentModeLabel" aria-live="polite"><?= $formValues['fulfillment_type'] === 'delivery' ? 'Cash on Delivery' : 'Cash on Pickup' ?></div>
                        <input type="hidden" name="payment_mode" value="cash">
                    </div>
                    <button class="btn btn-coffee btn-lg w-100" type="submit" <?= !$cartProducts ? 'disabled' : '' ?>>Send pre-order</button>
                    <p class="small text-secondary mt-3 mb-0">Pay cash when your delivery arrives, or at the counter for pickup. Pre-orders stay pending until the shop confirms them. The shop will confirm any delivery fee before dispatch.</p>
                </form>
            </div>
        </div>
    </section>
</main>

<footer class="py-4 mt-5">
    <div class="container d-flex justify-content-between flex-wrap gap-2">
        <span>Sweet Valley The Coffee</span>
        <a href="./">Back to home</a>
    </div>
</footer>
<script>
    const fulfillmentType = document.getElementById('fulfillmentType');
    const deliveryAddressGroup = document.getElementById('deliveryAddressGroup');
    const deliveryAddress = document.getElementById('deliveryAddress');
    const paymentModeLabel = document.getElementById('paymentModeLabel');
    function updateDeliveryAddressField() {
        const needsAddress = fulfillmentType.value === 'delivery';
        deliveryAddressGroup.hidden = !needsAddress;
        deliveryAddress.required = needsAddress;
        paymentModeLabel.textContent = needsAddress ? 'Cash on Delivery' : 'Cash on Pickup';
    }
    fulfillmentType.addEventListener('change', updateDeliveryAddressField);
    updateDeliveryAddressField();

    // Add products asynchronously, then refresh only the order panel from the server response.
    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.querySelector('input[name="action"]')?.value !== 'add_to_cart') return;
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        if (button?.disabled) return;
        const originalText = button?.textContent;
        if (button) { button.disabled = true; button.textContent = 'Adding…'; }

        try {
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow'
            });
            if (!response.ok) throw new Error('The order could not be updated.');
            const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const newCart = responseDocument.querySelector('#cart');
            const currentCart = document.querySelector('#cart');
            if (!newCart || !currentCart) throw new Error('The updated order could not be displayed.');
            currentCart.replaceWith(newCart);

            const newOrderLink = responseDocument.querySelector('a[href="#cart"]');
            const currentOrderLink = document.querySelector('a[href="#cart"]');
            if (newOrderLink && currentOrderLink) currentOrderLink.textContent = newOrderLink.textContent;

            const newNotice = responseDocument.querySelector('.customer-notice-overlay');
            const currentNotice = document.querySelector('.customer-notice-overlay');
            if (newNotice) {
                currentNotice?.remove();
                document.body.append(newNotice);
                newNotice.querySelector('[data-dismiss-notice]')?.focus({ preventScroll: true });
            } else {
                currentNotice?.remove();
            }
        } catch (error) {
            let overlay = document.querySelector('.customer-notice-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'customer-notice-overlay';
                document.body.append(overlay);
            }
            const notice = document.createElement('div');
            notice.className = 'alert alert-danger order-notice';
            notice.setAttribute('role', 'alertdialog');
            notice.setAttribute('aria-modal', 'true');
            notice.setAttribute('aria-label', 'Order notification');
            const message = document.createElement('p');
            message.className = 'notice-message';
            message.textContent = error.message || 'Could not add this item. Please try again.';
            const actions = document.createElement('div');
            actions.className = 'd-flex justify-content-end mt-3';
            const closeButton = document.createElement('button');
            closeButton.className = 'btn btn-coffee';
            closeButton.type = 'button';
            closeButton.setAttribute('data-dismiss-notice', '');
            closeButton.textContent = 'OK';
            actions.append(closeButton);
            notice.append(message, actions);
            overlay.replaceChildren(notice);
            closeButton.focus({ preventScroll: true });
        } finally {
            if (button?.isConnected) { button.disabled = false; button.textContent = originalText || 'Add'; }
        }
    });

    document.addEventListener('click', function (event) {
        const target = event.target;
        const dismissButton = target instanceof Element ? target.closest('[data-dismiss-notice]') : null;
        if (dismissButton) {
            dismissButton.closest('.customer-notice-overlay')?.remove();
        } else if (target instanceof Element && target.classList.contains('customer-notice-overlay')) {
            target.remove();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') document.querySelector('.customer-notice-overlay')?.remove();
    });
    document.querySelector('.customer-notice-overlay [data-dismiss-notice]')?.focus({ preventScroll: true });
</script>
</body>
</html>
