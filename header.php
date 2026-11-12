<?php 
require '../config/function.php';
require 'authentication.php';

?>

<!DOCTYPE html>
<html lang="en" style="height: 100%; background: #f3e8d7;">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Online Point Sale and Inventory Management System for The Valley | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />

    <link href="assets/css/styles.css" rel="stylesheet" />

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>

    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/default.min.css"/>

    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/beige-theme.css" rel="stylesheet" />

</head>

<body class="sb-nav-fixed" style="min-height: 100vh; margin: 0; padding: 0; display: flex; flex-direction: column;">


    <?php include('assets/includes/navbar.php'); ?>

    <div id="layoutSidenav">

        <?php include('assets/includes/sidebar.php'); ?>

             <div id="layoutSidenav_content">

                <main>
