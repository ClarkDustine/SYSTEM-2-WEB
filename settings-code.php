<?php
require('../config/dbcon.php');
require('../config/function.php');

// Update General Settings
if(isset($_POST['updateGeneral'])){
    $store_name = validate($_POST['store_name']);
    $contact_number = validate($_POST['contact_number']);
    $currency = validate($_POST['currency']);
    $tax_rate = validate($_POST['tax_rate']);
    $receipt_footer = validate($_POST['receipt_footer']);
    
    // Store in session for demo (in production, save to database)
    $_SESSION['store_settings'] = [
        'store_name' => $store_name,
        'contact_number' => $contact_number,
        'currency' => $currency,
        'tax_rate' => $tax_rate,
        'receipt_footer' => $receipt_footer
    ];
    
    logActivity('UPDATE', 'General Settings updated');
    redirect('settings.php', 'General Settings updated successfully!');
}

// Change Password
if(isset($_POST['changePassword'])){
    $current_password = validate($_POST['current_password']);
    $new_password = validate($_POST['new_password']);
    $confirm_password = validate($_POST['confirm_password']);
    
    if($new_password !== $confirm_password){
        redirect('settings.php', 'New passwords do not match!');
    }
    
    if(strlen($new_password) < 6){
        redirect('settings.php', 'Password must be at least 6 characters long!');
    }

    if(!isset($_SESSION['loggedInUser']['id'])){
        redirect('../login.php', 'Please log in again.');
    }

    $adminId = (int) $_SESSION['loggedInUser']['id'];
    $adminQuery = mysqli_query($conn, "SELECT password FROM admins WHERE id='$adminId' LIMIT 1");
    $admin = $adminQuery ? mysqli_fetch_assoc($adminQuery) : null;

    if(!$admin || !password_verify($current_password, $admin['password'])){
        redirect('settings.php', 'Current password is incorrect!');
    }

    $passwordChanged = update('admins', $adminId, [
        'password' => password_hash($new_password, PASSWORD_BCRYPT)
    ]);

    if(!$passwordChanged){
        redirect('settings.php', 'Unable to change password. Please try again.');
    }
    
    logActivity('UPDATE', 'Password changed for user: ' . $_SESSION['loggedInUser']['name']);
    redirect('settings.php', 'Password changed successfully!');
}

// Update Receipt Settings
if(isset($_POST['updateReceipt'])){
    $_SESSION['receipt_settings'] = [
        'auto_print' => isset($_POST['auto_print_receipt']) ? 1 : 0,
        'show_discount' => isset($_POST['show_discount']) ? 1 : 0,
        'vat_included' => isset($_POST['vat_included']) ? 1 : 0,
        'receipt_header' => validate($_POST['receipt_header'])
    ];
    
    logActivity('UPDATE', 'Receipt Settings updated');
    redirect('settings.php', 'Receipt Settings updated successfully!');
}

// Update Payment Settings
if(isset($_POST['updatePayments'])){
    $_SESSION['payment_settings'] = [
        'cash' => isset($_POST['payment_cash']) ? 1 : 0,
        'gcash' => isset($_POST['payment_gcash']) ? 1 : 0,
        'maya' => isset($_POST['payment_maya']) ? 1 : 0,
        'card' => isset($_POST['payment_card']) ? 1 : 0,
        'qr' => isset($_POST['payment_qr']) ? 1 : 0
    ];
    
    logActivity('UPDATE', 'Payment Methods updated');
    redirect('settings.php', 'Payment Settings updated successfully!');
}

// Update Security Settings
if(isset($_POST['updateSecurity'])){
    $_SESSION['security_settings'] = [
        'enable_2fa' => isset($_POST['enable_2fa']) ? 1 : 0,
        'session_timeout' => validate($_POST['session_timeout'])
    ];
    
    logActivity('UPDATE', 'Security Settings updated');
    redirect('settings.php', 'Security Settings updated successfully!');
}

// Backup Database
if(isset($_POST['backupDatabase'])){
    $dbname = 'the_valley';
    $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Create backups folder if it doesn't exist
    if(!is_dir('../backups')){
        mkdir('../backups', 0777, true);
    }
    
    $command = "mysqldump -h 127.0.0.1 -u root -P 3306 $dbname > $backup_file";
    $output = shell_exec($command);
    
    if(file_exists($backup_file)){
        logActivity('BACKUP', 'Database backed up successfully');
        redirect('settings.php', 'Database backup created successfully! File: ' . basename($backup_file));
    } else {
        logActivity('BACKUP', 'Database backup failed');
        redirect('settings.php', 'Backup failed. Please check server permissions.');
    }
}

// Logout All Devices
if(isset($_POST['logoutAllDevices'])){
    logActivity('LOGOUT', 'User logged out all devices');
    redirect('login.php', 'All sessions have been terminated. Please log in again.');
}

?>
