<?php $current = basename($_SERVER['PHP_SELF']); ?>

<a href="../home.php">
    <div class="sidebar-header">
        <center>
            <div class="logo-img">
                <img src="../images/logo1.png" alt="TinySteps Logo">
            </div>
            <h2>Tiny<span>Steps</span></h2>

        </center>
    </div>
</a>
<br>
<nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-line"></i> Tableau de bord
    </a>
    <a href="pediatres.php" class="nav-item <?php echo $current == 'pediatres.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-check"></i> Pédiatres
    </a>

    <a href="avis.php" class="nav-item <?php echo $current == 'avis.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-star"></i> Avis
    </a>
</nav>
<a href="logout.php" class="sidebar-logout">
    <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
</a>