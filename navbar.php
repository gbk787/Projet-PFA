<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="stylesheet" href="styles/user_nav.css">
</head>

<body>

    <header>
        <nav class="navbar">
            <a href="home.php">
                <div class="logo">
                    <img src="images/logo1.png" alt="Logo TinySteps" class="logo-img">
                    <span class="logo-text">Tiny <span>Steps</span></span>
                </div>
            </a>

            <ul class="nav-links">
                <li><a href="home.php" data-key="nav_home">Accueil</a></li>
                <li><a href="home.php#services" data-key="nav_services">Services</a></li>
                <li><a href="about.php" data-key="nav_about">À propos</a></li>
            </ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-nav">
                    <a href="<?= $_SESSION['user_role'] === 'medecin' ? 'profil_pediatre.php' : 'profil_parent.php' ?>">
                        <span class="user-nav-name">
                            <i class="fa-solid fa-user"></i>
                            <?php echo htmlspecialchars($_SESSION['user_nom']); ?>
                        </span>
                    </a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            <?php else: ?>
                <a href="connexion.php" class="btn-login" data-key="nav_login">Se connecter</a>
            <?php endif; ?>

        </nav>
    </header>