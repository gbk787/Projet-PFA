<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: connexion.php");
    exit;
}

require_once 'connexion_bd.php';

$id_medecin = $_SESSION['user_id'];
$nom_medecin = $_SESSION['user_nom'];

// Récupérer les infos du médecin
$stmt = $conn->prepare("SELECT nom, prenom, email, tel, ann_expr FROM medecins WHERE id = ?");
$stmt->bind_param("i", $id_medecin);
$stmt->execute();
$res = $stmt->get_result();
$medecin = $res->fetch_assoc();
$stmt->close();

// Compter les bébés suivis (via consultations)
$nb_patients = 0;
$res2 = $conn->query("SELECT COUNT(DISTINCT id_bebe) as nb FROM consultation WHERE id_medecin = $id_medecin");
if ($res2) {
    $row2 = $res2->fetch_assoc();
    $nb_patients = $row2['nb'];
}

// Dernières consultations
$consultations = array();
$res3 = $conn->query("SELECT c.id_consultation, b.nom_prenom, c.motif, c.date_consultation
                       FROM consultation c
                       JOIN bebe b ON c.id_bebe = b.id_bebe
                       WHERE c.id_medecin = $id_medecin
                       ORDER BY c.date_consultation DESC LIMIT 5");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $consultations[] = $row;
    }
}

$conn->close();

// Helpers pour ?? (null coalescing non disponible en PHP 5.3)
function val($arr, $key, $default = '') {
    return isset($arr[$key]) ? $arr[$key] : $default;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps - Espace Pédiatre</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style_accueilP.css">
    <link rel="icon" href="logo1.png" />
    <style>
        .stats-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .stat-card {
            flex: 1;
            min-width: 160px;
            background: white;
            border-radius: 16px;
            padding: 24px 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }
        .stat-icon.blue  { background: linear-gradient(135deg, #2b6e9e, #5ba3d9); }
        .stat-icon.green { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-icon.orange{ background: linear-gradient(135deg, #e67e22, #f39c12); }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .stat-label { font-size: 13px; color: var(--text-secondary); }

        .table-card { background: white; border-radius: 20px; padding: 24px; box-shadow: var(--shadow-sm); }
        .table-card h3 { margin-bottom: 16px; color: var(--text-primary); font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .5px; padding: 8px 12px; border-bottom: 1px solid #eef2f7; }
        td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f5f7fb; }
        tr:last-child td { border-bottom: none; }
        .badge-motif { background: #eef7ff; color: #2b6e9e; border-radius: 8px; padding: 3px 10px; font-size: 12px; }
        .empty-msg { color: var(--text-secondary); font-size: 14px; text-align: center; padding: 24px 0; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a href="home.php">
        <div class="sidebar-header">
            <div class="logo-img">
                <img src="images/logo1.png" alt="TinySteps Logo">
            </div>
            <h2>TinySteps</h2>
            <p style="font-size:12px;opacity:.7;margin-top:8px;">Espace Pédiatre</p>
        </div>
    </a>
    <nav class="sidebar-nav">
        <a href="accueil_pediatre.php" class="active"><i class="fas fa-home"></i> Tableau de bord</a>
        <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon Profil</a>
        <a href="consultation.php"><i class="fas fa-notes-medical"></i> Consultations</a>
        <a href="recherche_pediatre.php"><i class="fas fa-search"></i> Rechercher</a>
        <a href="messagerie.php"><i class="fas fa-envelope"></i> Messagerie</a>
    </nav>
    <div class="sidebar-footer">
        <div class="parent-info">
            <div class="parent-avatar">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="parent-details">
                <a href="recherche_pediatre.php" class="parent-name" style="text-decoration:none;color:inherit;">Dr. <?php echo htmlspecialchars($nom_medecin); ?></a>
                <div class="parent-email"><?php echo htmlspecialchars(val($medecin, 'email')); ?></div>
            </div>
        </div>
        <a href="deconnexion.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <header>
        <div class="header-title">
            <h1>Bonjour, Dr. <?php echo htmlspecialchars($nom_medecin); ?> 👋</h1>
            <p>Bienvenue dans votre espace pédiatre TinySteps</p>
        </div>
        <div class="header-actions">
            <button class="icon-btn"><i class="fas fa-bell"></i></button>
            <button class="icon-btn"><i class="fas fa-user-circle"></i></button>
        </div>
    </header>

    <div class="container">

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-baby"></i></div>
                <div>
                    <div class="stat-value"><?php echo $nb_patients; ?></div>
                    <div class="stat-label">Patients suivis</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="stat-value"><?php echo count($consultations); ?></div>
                    <div class="stat-label">Consultations récentes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-briefcase-medical"></i></div>
                <div>
                    <div class="stat-value"><?php echo htmlspecialchars(val($medecin, 'ann_expr', '—')); ?></div>
                    <div class="stat-label">Années d'expérience</div>
                </div>
            </div>
        </div>

        <!-- Informations médecin -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-md"></i>
                <h3>Mes informations</h3>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">Dr. <?php echo htmlspecialchars(val($medecin, 'nom')); ?> <?php echo htmlspecialchars(val($medecin, 'prenom')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars(val($medecin, 'email', '—')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo htmlspecialchars(val($medecin, 'tel', '—')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Expérience</div>
                    <div class="info-value"><?php echo htmlspecialchars(val($medecin, 'ann_expr', '—')); ?> ans</div>
                </div>
            </div>
        </div>

        <!-- Dernières consultations -->
        <div class="card card-full table-card">
            <div class="card-header">
                <i class="fas fa-notes-medical"></i>
                <h3>Dernières consultations</h3>
            </div>
            <?php if (empty($consultations)): ?>
                <p class="empty-msg"><i class="fas fa-inbox" style="margin-right:8px;"></i>Aucune consultation enregistrée pour le moment.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Motif</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations as $c): ?>
                    <tr>
                        <td><?php echo $c['id_consultation']; ?></td>
                        <td><i class="fas fa-baby" style="color:#5ba3d9;margin-right:6px;"></i><?php echo htmlspecialchars($c['nom_prenom']); ?></td>
                        <td><span class="badge-motif"><?php echo htmlspecialchars($c['motif']); ?></span></td>
                        <td><?php echo htmlspecialchars(val($c, 'date_consultation', '—')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>