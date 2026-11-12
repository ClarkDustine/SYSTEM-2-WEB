<?php include('assets/includes/header.php'); ?>

<div class="container-fluid px-4">
    <div class="card mt-4 shadow">
        <div class="card-header bg-light">
            <h4 class="mb-0">Activity Log</h4>
        </div>
        <div class="card-body">
            <?php alertMessage(); ?>

            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <input type="date" name="filterDate" class="form-control" value="<?= isset($_GET['filterDate']) ? $_GET['filterDate'] : ''; ?>" placeholder="Filter by date">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="activity-log.php" class="btn btn-danger">Reset</a>
                    </form>
                </div>
            </div>

            <?php
                $whereClause = "";
                if(isset($_GET['filterDate']) && !empty($_GET['filterDate'])){
                    $filterDate = $_GET['filterDate'];
                    $whereClause = "WHERE DATE(timestamp) = '$filterDate'";
                }
                
                // Create table if it doesn't exist
                $createTableQuery = "CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    admin_id INT,
                    admin_name VARCHAR(100),
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    ip_address VARCHAR(50),
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
                mysqli_query($conn, $createTableQuery);
                
                $logsQuery = mysqli_query($conn, "SELECT * FROM activity_logs $whereClause ORDER BY timestamp DESC LIMIT 500");
                if(!$logsQuery){
                    echo '<div class="alert alert-danger">Error fetching logs</div>';
                } else {
                    $logCount = mysqli_num_rows($logsQuery);
                }
            ?>

            <?php if($logCount > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logsQuery as $log): ?>
                                <tr>
                                    <td><?= $log['id']; ?></td>
                                    <td>
                                        <strong><?= $log['admin_name']; ?></strong>
                                        <?php if($log['admin_id']): ?>
                                            <br/><small class="text-muted">(ID: <?= $log['admin_id']; ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $action = $log['action'];
                                            $badges = [
                                                'LOGIN' => 'success',
                                                'LOGOUT' => 'warning',
                                                'CREATE' => 'info',
                                                'UPDATE' => 'primary',
                                                'DELETE' => 'danger',
                                                'VIEW' => 'secondary'
                                            ];
                                            $badgeColor = isset($badges[$action]) ? $badges[$action] : 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeColor; ?>"><?= $action; ?></span>
                                    </td>
                                    <td><?= !empty($log['details']) ? htmlspecialchars($log['details']) : '-'; ?></td>
                                    <td><small><?= $log['ip_address']; ?></small></td>
                                    <td><?= date('d M Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <small class="text-muted">Total logs displayed: <?= $logCount; ?> (Latest 500 shown)</small>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No activity logs found</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('assets/includes/footer.php'); ?>
