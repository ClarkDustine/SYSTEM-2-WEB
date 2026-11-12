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
?>

<div class="container-fluid px-4">
    <div class="card mt-4 shadow small">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Order Details</h4>
            <div>
                <a href="orders.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
                <?php if ($order): ?><a href="print-order.php?id=<?= (int) $order['id']; ?>&auto_print=1" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Print Receipt</a><?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$order): ?>
                <div class="alert alert-warning mb-0">The requested order could not be found.</div>
            <?php else: ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p class="mb-1"><strong>Invoice:</strong> <?= htmlspecialchars($order['invoice_no']); ?></p>
                        <p class="mb-1"><strong>Order Code:</strong> <?= htmlspecialchars($order['tracking_no']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <p class="mb-0"><strong>Payment:</strong> <?= htmlspecialchars(ucwords($order['payment_mode'])); ?></p>
                        <p class="mb-1"><strong>Fulfillment:</strong> <?= htmlspecialchars(ucfirst($order['fulfillment_type'] ?? 'pickup')); ?></p>
                        <?php if (($order['fulfillment_type'] ?? 'pickup') === 'delivery' && !empty($order['delivery_address'])): ?>
                            <p class="mb-0"><strong>Delivery address:</strong><br><?= nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer</h6>
                        <p class="mb-1"><strong><?= htmlspecialchars($order['customer_name'] ?: 'Walk-in Customer'); ?></strong></p>
                        <?php if (!empty($order['customer_phone'])): ?><p class="mb-1"><?= htmlspecialchars($order['customer_phone']); ?></p><?php endif; ?>
                        <?php if (!empty($order['customer_email'])): ?><p class="mb-0"><?= htmlspecialchars($order['customer_email']); ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead><tr><th>Product</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Line Total</th></tr></thead>
                        <tbody>
                            <?php if ($orderItems && mysqli_num_rows($orderItems) > 0): ?>
                                <?php while ($item = mysqli_fetch_assoc($orderItems)): ?>
                                    <tr><td><?= htmlspecialchars($item['product_name'] ?: 'Product #' . $item['product_id']); ?></td><td class="text-end">₱<?= number_format((float) $item['price'], 2); ?></td><td class="text-end"><?= (int) $item['quantity']; ?></td><td class="text-end">₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2); ?></td></tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No items recorded for this order.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end">₱<?= number_format((float) $order['total_amount'], 2); ?></th></tr></tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('assets/includes/footer.php'); ?>
