<?php
// ════════════════════════════════════════════════════════
//  liste_patients.php — corrigé selon la vraie structure BDD
//  Tables réelles : bebe (id_bebe, nom_prenom, date_naissance, sexe, id_parent)
//                   parent (id_parent, nom_prenom, telephone, email)
// ════════════════════════════════════════════════════════

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: connexion.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}
$conn->set_charset("utf8");

$id_medecin = (int) $_SESSION['user_id'];
$res_ped = $conn->query("SELECT * FROM medecin WHERE id = $id_medecin ");
$pediatre = $res_ped->fetch_assoc();

$today_str = date('Y-m-d');
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// ── Requête : jointure bebe + parent, filtrée par le médecin connecté ──────────
// La colonne du médecin dans bebe s'appelle "pediatre_id"
if ($search !== '') {
    $like = '%' . $conn->real_escape_string($search) . '%';
    $sql = "SELECT
                b.id_bebe,
                b.nom_prenom   AS nom_bebe,
                b.sexe,
                b.date_naissance,
                p.nom_prenom   AS nom_parent,
                p.telephone,
                p.email
            FROM bebe b
            LEFT JOIN parent p ON b.id_parent = p.id_parent
            WHERE b.pediatre_id = $id_medecin
              AND (
                    b.nom_prenom   LIKE '$like'
                 OR p.nom_prenom   LIKE '$like'
                 OR p.telephone    LIKE '$like'
                 OR p.email        LIKE '$like'
              )
            ORDER BY b.nom_prenom ASC";
} else {
    $sql = "SELECT
                b.id_bebe,
                b.nom_prenom   AS nom_bebe,
                b.sexe,
                b.date_naissance,
                p.nom_prenom   AS nom_parent,
                p.telephone,
                p.email
            FROM bebe b
            LEFT JOIN parent p ON b.id_parent = p.id_parent
            WHERE b.pediatre_id = $id_medecin
            ORDER BY b.nom_prenom ASC";
}

$result = $conn->query($sql);
$patients = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des patients - TinySteps</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style_accueilP.css">
    <link rel="stylesheet" href="styles/liste_patients.css?v=999">
    <link rel="icon" href="images/logo1.png">
</head>

<body>

    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <a href="home.php" class="sidebar-logo-link">
            <div class="sidebar-header">
                <div class="logo-img">
                    <img src="images/logo1.png" alt="TinySteps Logo">
                </div>
                <h2>TinySteps</h2>
                <p style="font-size:12px;opacity:.7;margin-top:8px;">Espace pediatre</p>
            </div>
        </a>
        <nav class="sidebar-nav">
            <a href="dashboard_pediatre.php"><i class="fas fa-home"></i> Tableau de bord</a>
            <a href="liste_patients.php" class="active"><i class="fas fa-baby"></i> Mes patients</a>
            <a href="agenda.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
            <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon profil</a>
        </nav>
        <div class="sidebar-footer">
            <div class="doctor-badge">
                <div class="doctor-avatar">
                    <?= strtoupper(substr($pediatre['prenom'] ?? $pediatre['nom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <a href="profil_pediatre.php" class="doctor-name" style="text-decoration:none;color:inherit;">
                        Dr.
                        <?= htmlspecialchars(($pediatre['prenom'] ?? '') . ' ' . ($pediatre['nom'] ?? 'Pédiatre')) ?>
                    </a>
                    <div class="doctor-role" style="font-size:11px;opacity:0.75;margin-top:2px;">
                        <?= htmlspecialchars($pediatre['email'] ?? 'Pédiatre') ?>
                    </div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>


    <!-- ── Main ── -->
    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Liste des patients</h1>
                <p>Cliquez sur un patient pour ouvrir son dossier.</p>
            </div>
            <form class="search-form" method="get" action="liste_patients.php">
                <input type="search" name="search" placeholder="Rechercher bébé, parent, tél, email..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </header>

        <section class="table-card">
            <div class="table-card-header">
                <span class="badge"><?php echo count($patients); ?> patient(s) trouvé(s)</span>
                <div class="table-description">Patients reliés à votre compte pédiatre.</div>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bébé</th>
                            <th>Sexe</th>
                            <th>Date de naissance</th>
                            <th>Parent</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Dossier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($patients) === 0): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    Aucun patient
                                    trouvé<?php echo $search ? ' pour "' . htmlspecialchars($search) . '"' : ''; ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients as $p): ?>
                                <tr onclick="window.location='dossier_patient.php?id_bebe=<?php echo (int) $p['id_bebe']; ?>'"
                                    style="cursor:pointer;">
                                    <td>
                                        <i class="fas fa-baby" style="color:#5ba3d9;margin-right:6px;"></i>
                                        <?php echo htmlspecialchars($p['nom_bebe']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sexe = strtoupper($p['sexe']);
                                        if ($sexe === 'M' || $sexe === 'GARCON') {
                                            echo '<i class="fas fa-mars" style="color:#5ba3d9;"></i> Garçon';
                                        } elseif ($sexe === 'F' || $sexe === 'FILLE') {
                                            echo '<i class="fas fa-venus" style="color:#e84393;"></i> Fille';
                                        } else {
                                            echo htmlspecialchars($p['sexe']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['date_naissance'] ?? '—'); ?></td>
                                    <td>
                                        <i class="fas fa-user" style="color:#888;margin-right:5px;"></i>
                                        <?php echo htmlspecialchars($p['nom_parent'] ?? '—'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['telephone'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($p['email'] ?? '—'); ?></td>
                                    <td><span class="link-label"><i class="fas fa-folder-open"></i> Voir dossier</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="liste_patient.js"></script>
</body>

</html>