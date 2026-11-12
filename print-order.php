<?php include('assets/includes/header.php'); ?>

<?php
$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$order = null;
$orderItems = null;

if ($orderId > 0) {
    $orderQuery = mysqli_query($conn, "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id = $orderId LIMIT 1");

    if ($orderQuery && mysqli_num_rows($orderQuery) === 1) {
        $order = mysqli_fetch_assoc($orderQuery);
        $orderItems = mysqli_query($conn, "SELECT oi.*, p.name AS product_name FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = $orderId ORDER BY oi.id ASC");
    }
}

$storeSettings = $_SESSION['store_settings'] ?? [];
$receiptSettings = $_SESSION['receipt_settings'] ?? [];
$storeName = $storeSettings['store_name'] ?? 'The Valley';
$contactNumber = $storeSettings['contact_number'] ?? '';
$currency = $storeSettings['currency'] ?? '₱';
$receiptHeader = trim($receiptSettings['receipt_header'] ?? '');
$receiptFooter = trim($storeSettings['receipt_footer'] ?? 'Thank you for your purchase!');
?>

<style>
    .receipt { max-width: 390px; margin: 2rem auto; padding: 24px; color: #1c1c1c; background: #fff; font-family: Arial, sans-serif; font-size: 13px; }
    .receipt__header, .receipt__footer { text-align: center; }
    .receipt__header h1 { margin: 0 0 6px; font-size: 22px; text-transform: uppercase; }
    .receipt__header p, .receipt__footer p { margin: 3px 0; white-space: pre-line; }
    .receipt__rule { border: 0; border-top: 1px dashed #555; margin: 16px 0; }
    .receipt__meta, .receipt__items { width: 100%; border-collapse: collapse; }
    .receipt__meta td { padding: 2px 0; vertical-align: top; }
    .receipt__meta td:last-child { text-align: right; }
    .receipt__items { margin-top: 6px; }
    .receipt__items th { border-bottom: 1px solid #333; padding: 5px 0; text-align: left; font-size: 11px; text-transform: uppercase; }
    .receipt__items td { padding: 7px 0; vertical-align: top; }
    .receipt__items th:nth-child(2), .receipt__items td:nth-child(2), .receipt__items th:last-child, .receipt__items td:last-child { text-align: right; }
    .receipt__total { display: flex; justify-content: space-between; font-size: 17px; font-weight: 700; }
    @media print {
        @page { margin: 8mm; }
        body * { visibility: hidden; }
        .receipt, .receipt * { visibility: visible; }
        .receipt { position: absolute; top: 0; left: 0; width: 100%; max-width: none; margin: 0; padding: 0; box-shadow: none; }
        .no-print { display: none !important; }
    }
</style>

<div class="container-fluid px-4 no-print">
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Order Receipt</h4>
        <div>
            <a href="<?= $order ? 'view-order.php?id=' . (int) $order['id'] : 'orders.php'; ?>" class="btn btn-outline-secondary btn-sm">Back</a>
            <?php if ($order): ?><button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print Receipt</button><?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$order): ?>
    <div class="container-fluid px-4"><div class="alert alert-warning mt-4">The requested order could not be found.</div></div>
<?php else: ?>
    <section class="receipt" aria-label="Receipt for <?= htmlspecialchars($order['invoice_no']); ?>">
        <header class="receipt__header">
            <h1><?= htmlspecialchars($storeName); ?></h1>
            <?php if ($receiptHeader !== ''): ?><p><?= nl2br(htmlspecialchars($receiptHeader)); ?></p><?php endif; ?>
            <?php if ($contactNumber !== ''): ?><p><?= htmlspecialchars($contactNumber); ?></p><?php endif; ?>
            <p><strong>OFFICIAL RECEIPT</strong></p>
        </header>
        <hr class="receipt__rule">
        <table class="receipt__meta">
            <tr><td>Invoice</td><td><?= htmlspecialchars($order['invoice_no']); ?></td></tr>
            <tr><td>Order Code</td><td><?= htmlspecialchars($order['tracking_no']); ?></td></tr>
            <tr><td>Order #</td><td><?= (int) $order['id']; ?></td></tr>
            <tr><td>Date</td><td><?= date('M d, Y h:i A', strtotime($order['created_at'])); ?></td></tr>
            <tr><td>Customer</td><td><?= htmlspecialchars($order['customer_name'] ?: 'Walk-in Customer'); ?></td></tr>
            <?php if (!empty($order['customer_phone'])): ?><tr><td>Phone</td><td><?= htmlspecialchars($order['customer_phone']); ?></td></tr><?php endif; ?>
            <tr><td>Payment</td><td><?= htmlspecialchars(ucwords($order['payment_mode'])); ?></td></tr>
            <tr><td>Fulfillment</td><td><?= htmlspecialchars(ucfirst($order['fulfillment_type'] ?? 'pickup')); ?></td></tr>
            <?php if (($order['fulfillment_type'] ?? 'pickup') === 'delivery' && !empty($order['delivery_address'])): ?><tr><td>Delivery address</td><td><?= nl2br(htmlspecialchars($order['delivery_address'])); ?></td></tr><?php endif; ?>
            <tr><td>Status</td><td><?= htmlspecialchars(ucfirst($order['order_status'])); ?></td></tr>
        </table>
        <hr class="receipt__rule">
        <table class="receipt__items">
            <thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
            <tbody>
                <?php if ($orderItems && mysqli_num_rows($orderItems) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($orderItems)): ?>
                        <tr><td><?= htmlspecialchars($item['product_name'] ?: 'Product #' . $item['product_id']); ?><br><small><?= htmlspecialchars($currency); ?><?= number_format((float) $item['price'], 2); ?> each</small></td><td><?= (int) $item['quantity']; ?></td><td><?= htmlspecialchars($currency); ?><?= number_format((float) $item['price'] * (int) $item['quantity'], 2); ?></td></tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No items recorded for this order.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <hr class="receipt__rule">
        <div class="receipt__total"><span>TOTAL</span><span><?= htmlspecialchars($currency); ?><?= number_format((float) $order['total_amount'], 2); ?></span></div>
        <hr class="receipt__rule">
        <footer class="receipt__footer"><p><?= nl2br(htmlspecialchars($receiptFooter)); ?></p></footer>
    </section>
    <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] === '1'): ?><script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script><?php endif; ?>
<?php endif; ?>

<?php include('assets/includes/footer.php'); ?>
