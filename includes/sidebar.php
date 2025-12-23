<?php
// Sidebar component - Pass $activePage variable to highlight active menu item
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <img src="images/logo.jpg" alt="Viros Logo" class="logo-img">
        <h2>Production MS</h2>
    </div>
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="line-management.php" class="nav-item <?php echo ($activePage == 'line-management') ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i>
            <span>Line Management</span>
        </a>
    </nav>
</div>
