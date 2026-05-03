<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: connexion.php");
    exit;
}

$isMedecin = $_SESSION['user_role'] === 'medecin';
$displayName = $_SESSION['user_nom'] ?? ($isMedecin ? ($_SESSION['nom_medecin'] ?? 'Pédiatre') : ($_SESSION['nom_parent'] ?? 'Parent'));

$displayEmail = $_SESSION['email'] ?? ($_SESSION['email_parent'] ?? ($_SESSION['email_medecin'] ?? 'email@exemple.com'));

// ── Vérification session : parent doit être connecté ──
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header("Location: connexion.php?role=parent");
    exit;
}

$parent_id = $_SESSION['user_id'];

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}
$id_parent = $_SESSION['user_id'] ?? null;
$nom_prenom = $email = '—';

if ($id_parent) {
    $st = $conn->prepare("SELECT nom_prenom, email FROM parent WHERE id_parent=? LIMIT 1");
    $st->bind_param("i", $id_parent);
    $st->execute();
    if ($p = $st->get_result()->fetch_assoc()) {
        $nom_prenom = $p['nom_prenom'];
        $email = $p['email'];
    }
    $st->close();
}

$parent_nom = $_SESSION['user_nom'] ?? $nom_prenom;
$parent_email = $_SESSION['user_email'] ?? $email;
$conn->set_charset("utf8mb4");

// Récupérer infos du parent connecté
$parent = [];
$stmtP = $conn->prepare("SELECT nom_prenom, email FROM parent WHERE id_parent = ?");
if ($stmtP) {
    $stmtP->bind_param("i", $parent_id);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    $parent = $resP->fetch_assoc() ?? [];
    $stmtP->close();
}


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Croissance - Poids & Taille</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <link rel="stylesheet" href="styles/croissance.css">
    <link rel="stylesheet" href="styles/style.css">

</head>


<body>
    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <a href="home.php" class="lien">
            <div class="sidebar-header">
                <div class="logo-img">
                    <img src="images/logo1.png" alt="TinySteps Logo">
                </div>
                <h2>TinySteps</h2>
                <p style="font-size:12px;opacity:.7;margin-top:8px;">
                    <?= $isMedecin ? 'Espace pediatre' : 'Espace parent' ?>
                </p>
            </div>
        </a>
        <nav class="sidebar-nav">
            <?php if ($isMedecin): ?>
                <a href="dashboard_pediatre.php"><i class="fas fa-home"></i> Tableau de bord</a>
                <a href="liste_patients.php" class="active"><i class="fas fa-baby"></i> Mes patients</a>
                <a href="calendriervaccin_pediatre.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
                <a href="rendez_vous.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
                <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon profil</a>
            <?php else: ?>
                <a href="accueil_parent.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="profil_parent.php"><i class="fas fa-user"></i> Profil</a>
                <a href="recherche_pediatre.php"><i class="fas fa-stethoscope"></i> Recherche pédiatre</a>
                <a href="croissance_simple.php" class="active"><i class="fas fa-chart-line"></i> Courbe de croissance</a>
                <a href="calendrier_vaccination.php"><i class="fas fa-syringe"></i> Calendrier vaccinal</a>
                <a href="conseils.php"><i class="fas fa-lightbulb"></i> Conseils personnalisés</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="parent-info">
                <div class="parent-avatar">
                    <?= strtoupper(substr($parent_nom, 0, 1)) ?>
                </div>
                <div>
                    <div class="parent-name"><?= htmlspecialchars($parent_nom) ?></div>
                    <div class="parent-email"><?= htmlspecialchars($parent_email) ?></div>
                </div>
            </div>

            <a href="home.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <div class="main">
        <div class="container">
            <div class="header">
                <h1 class="highlight">Suivi de Croissance</h1>
                <p class="subtitle">Poids et Taille - Évolution mensuelle</p>
            </div>

            <div class="main-content">
                <div class="charts-section">
                    <div class="chart-container">
                        <div class="chart-title">
                            Courbe du Poids (kg)
                        </div>
                        <canvas id="chartPoids"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            Courbe de la Taille (cm)
                        </div>
                        <canvas id="chartTaille"></canvas>
                    </div>
                </div>

                <div class="data-section">
                    <div class="data-title">
                        Données de Croissance
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mois</th>
                                <th>Poids (kg)</th>
                                <th>Taille (cm)</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                        </tbody>
                    </table>

                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-label">Poids Actuel</div>
                            <div class="stat-value" id="poidsActuel">-</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Taille Actuelle</div>
                            <div class="stat-value" id="tailleActuelle">-</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Gain de Poids</div>
                            <div class="stat-value" id="gainPoids">-</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Gain de Taille</div>
                            <div class="stat-value" id="gainTaille">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($isMedecin): ?>
                <div class="input-section">
                    <div class="input-title">+ Ajouter une nouvelle mesure</div>

                    <div class="input-group">
                        <div class="form-field">
                            <label>Mois </label>
                            <input type="number" id="mois" min="1" max="12" placeholder="1">
                        </div>

                        <div class="form-field">
                            <label>Poids (kg)</label>
                            <input type="number" id="poids" step="0.1" placeholder="5.0">
                        </div>

                        <div class="form-field">
                            <label>Taille (cm)</label>
                            <input type="number" id="taille" step="0.1" placeholder="55.0">
                        </div>

                        <div>
                            <button type="button" onclick="ajouterMesure()">Ajouter</button>
                        </div>
                    </div>

                    <div id="message" class="message"></div>
                </div>
            <?php else: ?>
                <div class="input-section">
                    <div class="input-title">Mode consultation</div>
                    <div class="message" style="display:block;">
                        Cette page est en lecture seule pour le parent.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="courbe.js"></script>
</body>

</html>