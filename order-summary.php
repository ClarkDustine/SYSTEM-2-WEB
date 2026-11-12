<?php
include('assets/includes/header.php');
if(!isset($_SESSION['productItems'])){
    echo '<script> window.location.href = "order-create.php"; </script>';
}
?>

 <div class="container-fluid px-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                <h2 class="mb-0">Order Summary</h2>
                <a href="order-create.php" class="btn btn-success btn-lg" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
                    <i class="fas fa-arrow-left"></i> Back to create order
                </a>
            </div>

            <?php alertMessage(); ?>

            <!-- Company Details Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="mb-2">The Valley</h4>
                    <p class="mb-1 text-muted">Coffee Shop</p>
                    <p class="mb-0 text-muted">The Valley Coffee Shop</p>
                </div>
            </div>

            <!-- Customer Details Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <?php
                    $invoiceNo = htmlspecialchars($_SESSION['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8');
                    $trackingNo = htmlspecialchars($_SESSION['tracking_no'] ?? '', ENT_QUOTES, 'UTF-8');
                    $cRowData = null;
                    $customerId = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;
                    if ($customerId > 0) {
                        $customerQuery = mysqli_query($conn, "SELECT * FROM customers WHERE id='$customerId' LIMIT 1");
                        if ($customerQuery && mysqli_num_rows($customerQuery) > 0) {
                            $cRowData = mysqli_fetch_assoc($customerQuery);
                        }
                    } elseif (!empty($_SESSION['cphone'])) {
                        $phone = validate($_SESSION['cphone']);
                        $customerQuery = mysqli_query($conn, "SELECT * FROM customers WHERE phone='$phone' LIMIT 1");
                        if ($customerQuery && mysqli_num_rows($customerQuery) > 0) {
                            $cRowData = mysqli_fetch_assoc($customerQuery);
                        }
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?php if ($cRowData): ?>
                                <h5 class="mb-3"><strong>Customer Details</strong></h5>
                                <p class="mb-2"><strong>Customer Name:</strong> <?= htmlspecialchars($cRowData['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mb-2"><strong>Customer Phone No.:</strong> <?= htmlspecialchars($cRowData['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mb-0"><strong>Customer Email:</strong> <?= htmlspecialchars($cRowData['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php else: ?>
                                <h5 class="mb-3"><strong>Walk-in Order</strong></h5>
                                <p class="mb-0 text-muted">No customer details were saved for this order.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5 class="mb-3"><strong>Order Tracking</strong></h5>
                            <p class="mb-2"><strong>Order Code:</strong> <?= $trackingNo ?></p>
                            <p class="mb-2"><strong>Invoice No.:</strong> <?= $invoiceNo ?></p>
                            <p class="mb-2"><strong>Invoice Date:</strong> <?= date('d M Y'); ?></p>
                            <p class="mb-0"><strong>Address:</strong> The Valley Coffee Shop</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Id</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grandTotal = 0;
                                $itemId = 1;
                                if(isset($_SESSION['productItems']) && !empty($_SESSION['productItems'])){
                                    foreach($_SESSION['productItems'] as $item) :
                                        $itemTotal = $item['price'] * $item['quantity'];
                                        $grandTotal += $itemTotal;
                                ?>
                                <tr>
                                    <td><?= $itemId++; ?></td>
                                    <td><?= $item['name']; ?></td>
                                    <td><?= number_format($item['price'], 0); ?></td>
                                    <td><?= $item['quantity']; ?></td>
                                    <td><strong><?= number_format($itemTotal, 0); ?></strong></td>
                                </tr>
                                <?php endforeach; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Grand Total Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Payment Mode:</strong> <?= isset($_SESSION['payment_mode']) ? validate($_SESSION['payment_mode']) : 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5 class="mb-0"><strong>Grand Total: <?= number_format($grandTotal, 0); ?></strong></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <form action="orders-code.php" method="POST" class="d-flex gap-2">
                        <a href="order-create.php" class="btn btn-secondary">Cancel</a>
                        <input type="hidden" name="placeOrder" value="1" />
                        <button type="submit" class="btn btn-warning ms-auto" style="width: 300px; font-size: 16px; font-weight: bold;">Save Order</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
 </div>

<?php include('assets/includes/footer.php'); ?>
