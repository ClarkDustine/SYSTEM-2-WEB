<?php

define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP MySQL account (change this if you set a password).
define('DB_PORT', 3306); // Default XAMPP MySQL/MariaDB port.
define('DB_DATABASE', 'the_valley');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

if(!$conn){
    die("Connection Failed: " . mysqli_connect_error() . ". Please verify DB credentials and port in config/dbcon.php.");
}

?>
