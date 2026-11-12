<?php include('assets/includes/header.php'); ?>

<div class="container-fluid px-4">
    <div class="card mt-4 shadow">
        <div class="card-header bg-light">
            <h4 class="mb-0">Settings</h4>
        </div>
        <div class="card-body">
            <?php alertMessage(); ?>

            <!-- Settings Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Users</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">Receipt</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">Payments</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">Security</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <h5 class="mb-3">General Store Settings</h5>
                    <form method="POST" action="settings-code.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Store Name</label>
                                <input type="text" name="store_name" class="form-control" value="The Valley" placeholder="Enter store name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" placeholder="09XX-XXX-XXXX">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-control">
                                    <option selected>PHP ₱</option>
                                    <option>USD $</option>
                                    <option>EUR €</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax Rate / VAT (%)</label>
                                <input type="number" name="tax_rate" class="form-control" value="12" step="0.01" placeholder="e.g., 12">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Receipt Footer Message</label>
                            <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Thank you for your purchase!"></textarea>
                        </div>
                        <button type="submit" name="updateGeneral" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>

                <!-- User Management Tab -->
                <div class="tab-pane fade" id="users" role="tabpanel">
                    <h5 class="mb-3">User Management</h5>
                    
                    <!-- Change Password Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Change Password</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="settings-code.php">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" name="changePassword" class="btn btn-warning">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- User Roles Info -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">User Roles</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Role</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Admin</strong></td>
                                        <td>Full system access, create orders, manage products, users, reports, settings</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cashier</strong></td>
                                        <td>Create orders, process payments, view reports</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Manager</strong></td>
                                        <td>Create orders, manage inventory, view all reports, export data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Receipt Settings Tab -->
                <div class="tab-pane fade" id="receipt" role="tabpanel">
                    <h5 class="mb-3">Receipt Settings</h5>
                    <form method="POST" action="settings-code.php">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_print_receipt" id="autoPrint" checked>
                                <label class="form-check-label" for="autoPrint">
                                    Auto Print Receipt
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_discount" id="showDiscount" checked>
                                <label class="form-check-label" for="showDiscount">
                                    Show Discount on Receipt
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vat_included" id="vatIncluded" checked>
                                <label class="form-check-label" for="vatIncluded">
                                    VAT Included in Price
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Receipt Header</label>
                            <textarea name="receipt_header" class="form-control" rows="2" placeholder="THE VALLEY&#10;Coffee Shop"></textarea>
                        </div>
                        <button type="submit" name="updateReceipt" class="btn btn-primary">Save Receipt Settings</button>
                    </form>
                </div>

                <!-- Payment Settings Tab -->
                <div class="tab-pane fade" id="payments" role="tabpanel">
                    <h5 class="mb-3">Payment Methods</h5>
                    <form method="POST" action="settings-code.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_cash" id="paymentCash" checked>
                                        <label class="form-check-label" for="paymentCash">
                                            Cash Payment
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_gcash" id="paymentGcash">
                                        <label class="form-check-label" for="paymentGcash">
                                            GCash
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_maya" id="paymentMaya">
                                        <label class="form-check-label" for="paymentMaya">
                                            Maya
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_card" id="paymentCard">
                                        <label class="form-check-label" for="paymentCard">
                                            Credit / Debit Card
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_qr" id="paymentQR">
                                        <label class="form-check-label" for="paymentQR">
                                            QR Payment
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="updatePayments" class="btn btn-primary">Save Payment Settings</button>
                    </form>
                </div>

                <!-- Security & Backup Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <h5 class="mb-3">Security & Backup</h5>
                    
                    <!-- Database Backup -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Database Backup</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Create a backup of your database to prevent data loss.</p>
                            <form method="POST" action="settings-code.php">
                                <button type="submit" name="backupDatabase" class="btn btn-success">
                                    <i class="fas fa-download"></i> Backup Database
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Options -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Security Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="enable_2fa" id="enable2FA">
                                    <label class="form-check-label" for="enable2FA">
                                        Enable Two-Factor Authentication (Coming Soon)
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p><strong>Session Timeout:</strong></p>
                                <input type="number" name="session_timeout" class="form-control" value="30" placeholder="Minutes"> minutes
                            </div>
                            <button type="submit" name="updateSecurity" class="btn btn-primary">Save Security Settings</button>
                        </div>
                    </div>

                    <!-- Logout All Devices -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Active Sessions</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">End all active sessions except the current one.</p>
                            <form method="POST" action="settings-code.php">
                                <button type="submit" name="logoutAllDevices" class="btn btn-danger">
                                    Logout All Devices
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('assets/includes/footer.php'); ?>
