<?php include('assets/includes/header.php'); ?>

<?php
function safeCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return $row ? (int)$row['total'] : 0;
}

function safeSum($conn, $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return $row ? (float)$row['total'] : 0;
}

$totalOrders = safeCount($conn, "SELECT COUNT(*) AS total FROM orders");
$todayOrders = safeCount($conn, "SELECT COUNT(*) AS total FROM orders WHERE DATE(order_date) = CURDATE()");
$totalProducts = safeCount($conn, "SELECT COUNT(*) AS total FROM products");
$totalCustomers = safeCount($conn, "SELECT COUNT(*) AS total FROM customers");

$totalSales = safeSum($conn, "SELECT SUM(total_amount) AS total FROM orders");
$todaySales = safeSum($conn, "SELECT SUM(total_amount) AS total FROM orders WHERE DATE(order_date) = CURDATE()");
$monthSales = safeSum($conn, "SELECT SUM(total_amount) AS total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())");

$lowStockProducts = mysqli_query($conn, "SELECT name, quantity FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 10");
$recentOrders = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 10");

$salesLabels = [];
$salesData = [];
$salesByDate = [];
$salesEndDate = new DateTimeImmutable('today');
$salesStartDate = $salesEndDate->modify('-6 days');

$salesChartQuery = mysqli_query($conn, "
    SELECT order_date AS sales_date, SUM(total_amount) AS total_sales
    FROM orders
    WHERE order_date BETWEEN '{$salesStartDate->format('Y-m-d')}' AND '{$salesEndDate->format('Y-m-d')}'
    GROUP BY order_date
    ORDER BY sales_date ASC
");

if ($salesChartQuery) {
    while ($row = mysqli_fetch_assoc($salesChartQuery)) {
        $salesByDate[$row['sales_date']] = (float)$row['total_sales'];
    }
}

for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
    $salesDate = $salesStartDate->modify("+{$dayOffset} days");
    $salesDateKey = $salesDate->format('Y-m-d');
    $salesLabels[] = $salesDate->format('M d');
    $salesData[] = $salesByDate[$salesDateKey] ?? 0;
}

$productLabels = [];
$productQty = [];

$productChartQuery = mysqli_query($conn, "
    SELECT name, quantity 
    FROM products 
    ORDER BY quantity DESC 
    LIMIT 5
");

if ($productChartQuery) {
    while ($row = mysqli_fetch_assoc($productChartQuery)) {
        $productLabels[] = htmlspecialchars($row['name']);
        $productQty[] = (int)$row['quantity'];
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Reports</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Sales and inventory overview</li>
    </ol>

    <?php alertMessage(); ?>

    <div class="row g-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" style="color: #f5f5f7;">₱<?= number_format($todaySales, 2); ?></h5>
                            <small style="color: #8a8d96;">Today's Sales</small>
                        </div>
                        <div style="font-size: 2rem; color: #28a745; opacity: 0.8;"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" style="color: #f5f5f7;">₱<?= number_format($monthSales, 2); ?></h5>
                            <small style="color: #8a8d96;">This Month Sales</small>
                        </div>
                        <div style="font-size: 2rem; color: #80614b; opacity: 0.8;"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" style="color: #f5f5f7;"><?= $totalOrders; ?></h5>
                            <small style="color: #8a8d96;">Total Orders</small>
                        </div>
                        <div style="font-size: 2rem; color: #a66a3f; opacity: 0.8;"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1" style="color: #f5f5f7;"><?= $totalProducts; ?></h5>
                            <small style="color: #8a8d96;">Total Products</small>
                        </div>
                        <div style="font-size: 2rem; color: #6c757d; opacity: 0.8;"><i class="fas fa-box-open"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sales in the Last 7 Days</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Stock Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="productChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card mt-4 shadow small">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Low Stock Products</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lowStockProducts && mysqli_num_rows($lowStockProducts) > 0): ?>
                                <?php while ($item = mysqli_fetch_assoc($lowStockProducts)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']); ?></td>
                                        <td><span class="badge bg-danger"><?= $item['quantity']; ?> left</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center">No low stock products</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card mt-4 shadow small">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Recent Orders</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentOrders && mysqli_num_rows($recentOrders) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recentOrders)): ?>
                                    <tr>
                                        <td>#<?= $order['id']; ?></td>
                                        <td><?= date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No recent orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <a href="orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js default colors for dark theme
Chart.defaults.color = '#8a8d96';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

const salesCtx = document.getElementById('salesChart');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($salesLabels); ?>,
        datasets: [{
            label: 'Sales',
            data: <?= json_encode($salesData); ?>,
            borderColor: '#6a4127',
            backgroundColor: 'rgba(106, 65, 39, 0.12)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#6a4127',
            pointBorderColor: '#fffaf1',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { grid: { display: false, color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#8a8d96' } },
            y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#8a8d96' } }
        }
    }
});

const productCtx = document.getElementById('productChart');
new Chart(productCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($productLabels); ?>,
        datasets: [{
            data: <?= json_encode($productQty); ?>,
            backgroundColor: ['#6a4127', '#65764d', '#a66a3f', '#c28a45', '#80614b'],
            borderColor: 'rgba(15, 15, 20, 0.9)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { color: '#8a8d96' } }
        }
    }
});
</script>

<?php include('assets/includes/footer.php'); ?>
