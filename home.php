<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/conseils.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<?php include('navbar.php') ?>
<main>
    <section class="hero">
        <div class="hero-grid-bg"></div>
        <div class="hero-content">
            <h1>Le lien de <span class="highlight">confiance </span>entre vous et votre <span class="highlight">
                    pédiatre</span></h1>
            <p>Gérez la santé de votre bébé en toute simplicité. Suivez sa croissance, ses vaccinations et recevez des
                conseils personnalisés directement sur votre plateforme sécurisée.</p>
            <div class="service">
                <h2 class="highlight-2">Commencez en tant que</h2>
                <br>
                <div class="hero-btns">
                    <a href="inscrir_pediatre.php" class="btn-primary"> Pediatre</a>
                    <a href="inscrir_parent.php" class="btn-primary">Parent</a>
                </div>
            </div>
        </div>
        <div class="hero-image-container">
            <div class="hero-illustration"></div>
        </div>
    </section>

    <section id="services">
        <div class="head">
            <h2><span class="dot"></span> Nos Services pour votre Bébé <span class="dot"></span></h2>
        </div>
        <div class="services-grid">
            <div class="card">
                <h3><i class="fa-solid fa-id-card"></i> Profil Bébé</h3>
                <p>Centralisez toutes les informations de santé de votre enfant en un seul lieu sécurisé.</p>
            </div>
            <div class="card">
                <h3><i class="fa-solid fa-chart-line"></i> Suivi de Croissance</h3>
                <p>Visualisez l'évolution de la taille et du poids avec des courbes précises et interactives.</p>
            </div>
            <div class="card">
                <h3><i class="fa-solid fa-syringe"></i> Vaccinations</h3>
                <p>Ne manquez aucun rappel grâce à notre système de suivi et de notifications personnalisé.</p>
            </div>
            <div class="card conseils-card">
                <h3><i class="fa-solid fa-lightbulb"></i> Conseils Experts</h3>
                <p>Survolez les cartes avec votre souris pour découvrir les catégories et conseils des pédiatres.</p>
                <div class="categories-hover">
                    <a href="conseils.php?conseil=alimentation">Alimentation</a>
                    <a href="conseils.php?conseil=sommeil">Sommeil</a>
                    <a href="conseils.php?conseil=developpement">Développement</a>
                    <a href="conseils.php?conseil=sante">Hygiène/Santé</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include('footer.php') ?>