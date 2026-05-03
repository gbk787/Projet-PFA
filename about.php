<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos — TinySteps</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/about.css">



</head>

<body>
    <?php include('navbar.php') ?>

    <!-- ══════════════════════════════════════════════════
         HERO
    ══════════════════════════════════════════════════ -->
    <div class="terms-hero">
        <div class="hero-badge">Notre histoire</div>
        <h1>À propos de <span>TinySteps</span></h1>
        <p>Parce que chaque petit pas compte dans la grande aventure de la parentalité</p>
    </div>

    <!-- ══════════════════════════════════════════════════
         STORY
    ══════════════════════════════════════════════════ -->
    <section id="story" class="story-section">
        <div class="story-inner">

            <!-- Text -->
            <div class="story-text">
                <div class="section-label">Notre histoire</div>
                <h2 class="section-titlee">Née d'une <em>vraie</em><br>frustration parentale</h2>
                <p class="section-sub">En Tunisie, le suivi médical de l'enfant est souvent fragmenté. Carnet de santé
                    papier égaré, vaccins oubliés, croissance non suivie, pédiatre difficile à joindre entre deux
                    consultations…</p>
                <p class="section-sub">TinySteps est né de cette réalité. Une plateforme qui centralise tout — du
                    premier vaccin au dernier rendez-vous — et qui crée un vrai lien numérique entre les parents et les
                    professionnels de santé.</p>
                <p class="section-sub">Développé dans le cadre d'un projet de fin d'études, il incarne notre conviction
                    :
                    la technologie peut simplifier et humaniser le soin.</p>
            </div>

            <!-- Visual card -->
            <div class="story-visual">
                <div class="story-card">
                    <ul class="problem-list">
                        <li>
                            <div class="tl-dot"><i class="fas fa-folder-open"></i></div>
                            <div class="tl-content">
                                <h4>Dossiers dispersés</h4>
                                <p>Tout centralisé dans un profil enfant unique</p>
                            </div>
                        </li>
                        <li>
                            <div class="tl-dot"><i class="fas fa-syringe"></i></div>
                            <div class="tl-content">
                                <h4>Vaccins oubliés</h4>
                                <p>Alertes automatiques et calendrier vaccinal visuel</p>
                            </div>
                        </li>
                        <li>
                            <div class="tl-dot"><i class="fas fa-chart-line"></i></div>
                            <div class="tl-content">
                                <h4>Croissance non surveillée</h4>
                                <p>Courbes OMS interactives avec détection d'anomalies</p>
                            </div>
                        </li>
                        <li>
                            <div class="tl-dot"><i class="fas fa-stethoscope"></i></div>
                            <div class="tl-content">
                                <h4>Pédiatre inaccessible</h4>
                                <p>Messagerie directe et prise de rendez-vous en ligne</p>
                            </div>
                        </li>
                        <li>
                            <div class="tl-dot"><i class="fas fa-robot"></i></div>
                            <div class="tl-content">
                                <h4>Manque d'orientation</h4>
                                <p>Assistant IA pour conseils personnalisés en temps réel</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         VALUES
    ══════════════════════════════════════════════════ -->
    <section class="values-section">
        <div class="values-inner">
            <div class="values-header">
                <div class="section-label light">Ce en quoi nous croyons</div>
                <h2 class="section-titlee">Nos valeurs fondamentales</h2>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Sécurité des données</h3>
                    <p>Les informations médicales de votre enfant sont chiffrées et protégées. Votre vie privée est
                        notre priorité absolue.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-handshake"></i></div>
                    <h3>Transparence totale</h3>
                    <p>Aucune information cachée, aucune surprise. Vous savez toujours ce que nous faisons de vos
                        données, toujours.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-headset"></i></div>
                    <h3>Assistance 7j/7</h3>
                    <p>Notre équipe est joignable à tout moment, parce qu'un parent inquiet n'attend pas les heures
                        ouvrables.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <h3>Suivi personnalisé</h3>
                    <p>Chaque enfant est unique. TinySteps s'adapte au profil de votre enfant pour un accompagnement
                        sur mesure.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-bolt"></i></div>
                    <h3>Simple &amp; rapide</h3>
                    <p>De la création du profil à la consultation en quelques minutes. La plateforme est pensée pour la
                        simplicité.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <h3>Expertise médicale</h3>
                    <p>Tous nos contenus sont validés par des pédiatres certifiés pour vous garantir une information
                        fiable.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         WHY TINYSTEPS
    ══════════════════════════════════════════════════ -->
    <section class="whyy-section">
        <div class="whyy-header">
            <h2>Pourquoi choisir <span>TinySteps</span> ?</h2>
            <p>La façon la plus simple de rester connecté à la santé de votre enfant</p>
        </div>
        <div class="whyy-grid">
            <div class="whyy-card">
                <div class="whyy-icon"><i class="fas fa-file-medical"></i></div>
                <h3>Carnet numérique</h3>
                <p>Un carnet de santé complet, toujours à jour, accessible depuis n'importe quel appareil.</p>
            </div>
            <div class="whyy-card">
                <div class="whyy-icon"><i class="fas fa-bell"></i></div>
                <h3>Rappels automatiques</h3>
                <p>Ne manquez plus aucun vaccin ni consultation grâce à nos rappels intelligents personnalisés.</p>
            </div>
            <div class="whyy-card">
                <div class="whyy-icon"><i class="fas fa-comments"></i></div>
                <h3>Conseils personnalisés</h3>
                <p>Des conseils personnalisés selon l'âge et la catégorie, avec un chatbot intelligent intégré.</p>
            </div>
            <div class="whyy-card">
                <div class="whyy-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Courbes de croissance</h3>
                <p>Visualisez l'évolution du poids, de la taille et du périmètre crânien en temps réel.</p>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════
         CTA
    ══════════════════════════════════════════════════ -->
    <section class="cta-section">
        <div class="cta-inner">
            <div class="section-label light">Prêt à commencer ?</div>
            <h2 class="section-titlee">Rejoignez TinySteps aujourd'hui</h2>
            <p>Créez le profil de votre enfant en quelques minutes et bénéficiez d'un suivi médical simplifié,
                sécurisé et toujours accessible.</p>
            <a href="inscrir_parent.php" class="btn-cta">
                <i class="fa-solid fa-baby"></i> Créer un profil gratuitement
            </a>
        </div>
    </section>
    <?php include('footer.php') ?>
</body>

</html>