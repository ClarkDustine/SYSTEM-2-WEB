<?php

require 'config/function.php';

if(isset($_SESSION['logged_in'])){
    $userName = isset($_SESSION['loggedInUser']['name']) ? $_SESSION['loggedInUser']['name'] : 'User';
    logActivity('LOGOUT', 'Admin logged out: ' . $userName);
    logoutSession();
}

redirect('login.php', 'Logged Out Successfully.');

?>