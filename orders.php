<?php include('assets/includes/header.php'); ?>

<div class="container-fluid px-4">
    <div class="card mt-4 shadow small">
        <div class="card-header">
            <h4 class="mb-0">Orders</h4>
        </div>
        <div class="card-body">
            <?php alertMessage(); ?>

            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label for="filterDate" class="form-label">Filter by Date</label>
                            <input type="date" id="filterDate" name="filterDate" class="form-control" value="<?= isset($_GET['filterDate']) ? $_GET['filterDate'] : ''; ?>" style="font-family: inherit; font-size: 1rem;">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="orders.php" class="btn btn-danger">Reset</a>
                    </form>
                </div>
            </div>

            <?php
                $whereClause = "";
                if(isset($_GET['filterDate']) && !empty($_GET['filterDate'])){
                    $filterDate = $_GET['filterDate'];
                    $whereClause = "WHERE DATE(o.order_date) = '$filterDate'";
                }
                
                $ordersQuery = mysqli_query($conn, "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone FROM orders o LEFT JOIN customers c ON o.customer_id = c.id $whereClause ORDER BY o.id DESC");
                if(!$ordersQuery){
                    echo '<h4> Something went wrong!</h4>';
                    return false;
                }
            ?>

            <?php if(mysqli_num_rows($ordersQuery) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Invoice No</th>
                                <th>Order Code</th>
                                <th>Customer</th>
                                <th>Fulfillment</th>
                                <th>Payment Mode</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ordersQuery as $order): ?>
                                <tr>
                                    <td><?= $order['id']; ?></td>
                                    <td><?= $order['invoice_no']; ?></td>
                                    <td><?= htmlspecialchars($order['tracking_no']); ?></td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name'] ?: 'Walk-in Order'); ?>
                                        <?php if (!empty($order['customer_phone'])): ?><br/><small><?= htmlspecialchars($order['customer_phone']); ?></small><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst($order['fulfillment_type'] ?? 'pickup')); ?></td>
                                    <td><?= $order['payment_mode']; ?></td>
                                    <td><?= number_format($order['total_amount'], 0); ?></td>
                                    <td>
                                        <?php if($order['order_status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif($order['order_status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif($order['order_status'] == 'cancelled'): ?>
                                            <span class="badge bg-danger">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($order['order_status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php if($order['order_status'] == 'pending'): ?>
                                                <form action="orders-code.php" method="POST" class="d-flex gap-1">
                                                    <input type="hidden" name="order_id" value="<?= $order['id']; ?>" />
                                                    <button type="submit" name="updateOrderStatus" value="completed" class="btn btn-sm btn-success">Complete</button>
                                                    <button type="submit" name="updateOrderStatus" value="cancelled" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="view-order.php?id=<?= (int) $order['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            <a href="print-order.php?id=<?= (int) $order['id']; ?>&auto_print=1" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">Print</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <h4 class="mb-0">No record found</h4>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('assets/includes/footer.php'); ?>
