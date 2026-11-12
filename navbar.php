<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3 d-flex align-items-center gap-2" href="index.php" aria-label="The Valley dashboard"><img class="site-logo" src="assets/img/removedbackgroundlogo.png" alt="" width="32" height="32" style="display:block;width:32px !important;height:32px !important;max-width:32px;max-height:32px;object-fit:contain;flex:0 0 32px;"><span>The Valley</span></a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
  <!-- Navbar-->
  <ul class="navbar-nav ms-auto me-3 me-lg-4">
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user fa-fw"></i>
          <?= $_SESSION['loggedInUser']['name']; ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
        <li><a class="dropdown-item" href="activity-log.php">Activity Log</a></li>
        <li><hr class="dropdown-divider" /></li>
        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
      </ul>
    </li>
  </ul>
</nav>
