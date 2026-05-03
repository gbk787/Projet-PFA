<?php
// ============================================
// TinySteps - Agenda Pédiatre
// agenda.php
// ============================================

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: connexion.php?role=medecin");
    exit;
}

// --- Connexion à la base de données ---
$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// --- Session --- ✅ CORRIGÉ
$pediatre_id = (int) $_SESSION['user_id'];

// --- Récupérer les infos du pédiatre --- ✅ CORRIGÉ
$stmt = $conn->prepare("SELECT * FROM medecin WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $pediatre_id);
$stmt->execute();
$pediatre = $stmt->get_result()->fetch_assoc();
$stmt->close();
// --- Date sélectionnée ---
$date_selectionnee = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$ts = strtotime($date_selectionnee);
$today_str = date('Y-m-d');
$date_prev = date('Y-m-d', strtotime('-1 day', $ts));
$date_suiv = date('Y-m-d', strtotime('+1 day', $ts));

// --- Mois / Année pour le calendrier ---
$mois = isset($_GET['mois']) ? (int) $_GET['mois'] : (int) date('m');
$annee = isset($_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');
if ($mois < 1) {
    $mois = 12;
    $annee--;
}
if ($mois > 12) {
    $mois = 1;
    $annee++;
}
$mois_prev = $mois - 1 ?: 12;
$annee_prev = ($mois == 1) ? $annee - 1 : $annee;
$mois_suiv = $mois % 12 + 1;
$annee_suiv = ($mois == 12) ? $annee + 1 : $annee;


// ============================================
// TRAITEMENTS AJAX (POST)
// ============================================

// --- Nouveau RDV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nouveau_rdv') {
    header('Content-Type: application/json');
    $bebe_id = (int) $_POST['bebe_id'];
    $date_rdv = $conn->real_escape_string($_POST['date_rdv'] ?? '');
    $heure_debut = $conn->real_escape_string($_POST['heure_debut'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['consult', 'suivi', 'urgence']) ? $_POST['type'] : 'consult';
    $motif = $conn->real_escape_string(trim($_POST['motif'] ?? ''));

    if (!$bebe_id || !$date_rdv || !$heure_debut) {
        echo json_encode(['success' => false, 'message' => 'Champs requis manquants.']);
        exit;
    }
    $heure_fin = date('H:i:s', strtotime($heure_debut . ' +30 minutes'));

    $stmt = $conn->prepare("
        INSERT INTO rendez_vous (pediatre_id, bebe_id, date_rdv, heure_debut, heure_fin, type, motif, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'attente')
    ");
    $stmt->bind_param("iisssss", $pediatre_id, $bebe_id, $date_rdv, $heure_debut, $heure_fin, $type, $motif);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rendez-vous créé avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création.']);
    }
    $stmt->close();
    exit;
}

// --- Confirmer un RDV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirmer_rdv') {
    header('Content-Type: application/json');
    $rdv_id = (int) $_POST['rdv_id'];
    $stmt = $conn->prepare("UPDATE rendez_vous SET statut='confirmé' WHERE id=? AND pediatre_id=?");
    $stmt->bind_param("ii", $rdv_id, $pediatre_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Rendez-vous confirmé.']);
    exit;
}

// --- Annuler un RDV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler_rdv') {
    header('Content-Type: application/json');
    $rdv_id = (int) $_POST['rdv_id'];
    $stmt = $conn->prepare("UPDATE rendez_vous SET statut='annulé' WHERE id=? AND pediatre_id=?");
    $stmt->bind_param("ii", $rdv_id, $pediatre_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Rendez-vous annulé.']);
    exit;
}

// ============================================
// RÉCUPÉRATION DES DONNÉES
// ============================================

// Médecin (table: medecin)
$result = $conn->query("SELECT * FROM medecin WHERE id = $pediatre_id LIMIT 1");
$pediatre = $result->fetch_assoc();

// Liste des bébés/patients (table: bebe)
$bebes = $conn->query("SELECT b.*, p.nom_prenom as parent_nom 
                       FROM bebe b 
                       LEFT JOIN parent p ON b.id_parent = p.id_parent 
                       ORDER BY b.nom_prenom")->fetch_all(MYSQLI_ASSOC);

// Stats du jour
$stmt = $conn->prepare("SELECT statut, COUNT(*) as total FROM rendez_vous WHERE pediatre_id=? AND date_rdv=? GROUP BY statut");
$stmt->bind_param("is", $pediatre_id, $date_selectionnee);
$stmt->execute();
$stats_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stats = ['confirmé' => 0, 'attente' => 0, 'annulé' => 0, 'total' => 0];
foreach ($stats_raw as $s) {
    $stats[$s['statut']] = (int) $s['total'];
    $stats['total'] += (int) $s['total'];
}

// RDV du jour
$stmt = $conn->prepare("
    SELECT r.*, 
           COALESCE(b.nom_prenom, 'Patient inconnu') as bebe_nom,
           b.date_naissance, 
           b.sexe
    FROM rendez_vous r
    LEFT JOIN bebe b ON b.id_bebe = r.bebe_id
    WHERE r.pediatre_id = ? AND r.date_rdv = ?
    ORDER BY r.heure_debut ASC
");

$stmt->bind_param("is", $pediatre_id, $date_selectionnee);
$stmt->execute();
$rdvs_du_jour = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Index par heure
$rdvs_par_heure = [];
foreach ($rdvs_du_jour as $rdv) {
    $h = substr($rdv['heure_debut'], 0, 5);
    $rdvs_par_heure[$h] = $rdv;
}

// Jours avec RDV dans le mois
$stmt = $conn->prepare("SELECT DAY(date_rdv) as jour FROM rendez_vous WHERE pediatre_id=? AND MONTH(date_rdv)=? AND YEAR(date_rdv)=? GROUP BY date_rdv");
$stmt->bind_param("iii", $pediatre_id, $mois, $annee);
$stmt->execute();
$jours_avec_rdv = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'jour');
$stmt->close();

// Disponibilités
$stmt = $conn->prepare("SELECT * FROM disponibilites WHERE medecin_id=?");
$stmt->bind_param("i", $pediatre_id);
$stmt->execute();
$dispos_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ CORRECTION — garde tous les créneaux par jour
$dispos = [];
foreach ($dispos_raw as $d) {
    $jour = $d['jour_semaine'];
    if (!isset($dispos[$jour])) {
        $dispos[$jour] = [
            'creneaux' => [],
            'actif' => (bool) $d['actif'],
        ];
    }
    // Ajoute chaque créneau du jour
    $dispos[$jour]['creneaux'][] = [
        'debut' => substr($d['heure_debut'], 0, 5),
        'fin' => substr($d['heure_fin'], 0, 5),
    ];
}

// Helpers
function age_patient(?string $date_naissance): string
{
    if ($date_naissance && $date_naissance !== '0000-00-00') {
        $diff = (new DateTime())->diff(new DateTime($date_naissance));
        if ($diff->y >= 1)
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        if ($diff->m >= 1)
            return $diff->m . ' mois';
        return $diff->d . ' j';
    }
    return '—';
}

function dateLongFR(string $date): string
{
    $jours_longs = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $mois_fr = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $ts = strtotime($date);
    return $jours_longs[(int) date('N', $ts)] . ' ' . date('j', $ts) . ' ' . $mois_fr[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function creneau_disponible($creneau, $dow, $dispos)
{
    if (!isset($dispos[$dow]) || !$dispos[$dow]['actif'])
        return false;
    foreach ($dispos[$dow]['creneaux'] as $c) {
        if ($creneau >= $c['debut'] && $creneau < $c['fin']) {
            return true;
        }
    }
    return false;
}

$jours_fr = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$mois_fr = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$dow = (int) date('N', $ts);

// Créneaux 08:00 → 17:30
$creneaux = [];
for ($h = 8; $h <= 17; $h++) {
    $creneaux[] = sprintf('%02d:00', $h);
    $creneaux[] = sprintf('%02d:30', $h);
}

$type_icons = ['consult' => '🩺', 'suivi' => '📈', 'urgence' => '🚨'];
$type_labels = ['consult' => 'Consultation', 'suivi' => 'Suivi', 'urgence' => 'Urgence'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Pédiatre – TinySteps</title>
    <link rel="stylesheet" href="styles/agenda.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

</head>

<body>

    <!-- ======== SIDEBAR ======== -->
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
            <a href="liste_patients.php"><i class="fas fa-baby"></i> Mes patients</a>
            <a href="agenda.php" class="active"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
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

    <!-- ======== MAIN ======== -->
    <div class="page-wrapper">


        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="fas fa-calendar-check"></i> Agenda Pédiatre</h1>
                <p>Bonjour Dr. <?= htmlspecialchars($pediatre['nom'] ?? '') ?> — <?= dateLongFR($today_str) ?></p>
            </div>
            <div class="topbar-right">
                <span class="today-pill"><i class="fas fa-calendar-day"></i> Aujourd'hui</span>

                <!-- Notifications RDV en attente -->
                <div class="notif-wrapper">
                    <button class="icon-btn" id="btn-notif" title="Demandes de RDV">
                        <i class="fas fa-bell"></i>
                        <span class="badge-notif" id="notif-count" style="display:none">0</span>
                    </button>
                    <div class="notif-panel" id="notif-panel">
                        <div class="notif-panel-head">
                            <span><i class="fas fa-calendar-clock"></i> Demandes de RDV</span>
                            <span class="notif-panel-badge" id="notif-badge-txt">0 en attente</span>
                        </div>
                        <div class="notif-panel-body" id="notif-list">
                            <div class="notif-empty"><i class="fas fa-check-circle"></i>Aucune demande en attente</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Grid : Médecin + Calendrier -->
        <div class="top-grid">
            <!-- Carte Médecin -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon">🩺</div>
                    <h2>Pédiatre</h2>
                </div>
                <?php if ($pediatre): ?>
                    <div class="doctor-info">
                        <div class="doctor-avatar">
                            <?= strtoupper(substr($pediatre['prenom'], 0, 1) . substr($pediatre['nom'], 0, 1)) ?>
                        </div>
                        <div class="doctor-details">
                            <div class="doctor-name">Dr.
                                <?= htmlspecialchars($pediatre['prenom'] . ' ' . $pediatre['nom']) ?>
                            </div>
                            <div class="doctor-specialty">
                                <?= htmlspecialchars($pediatre['specialite'] ?? 'Pédiatre généraliste') ?>
                            </div>
                            <div class="doctor-location">📍 Cabinet El Menzah, Tunis</div>
                        </div>
                        <div class="status-badge">● En ligne</div>
                    </div>

                    <!-- ========== HORAIRES DE CONSULTATION CLIQUABLES ========== -->
                    <div class="horaires-section">
                        <div class="horaires-title">
                            <i class="fas fa-clock"></i> Horaires de consultation
                        </div>
                        <div class="horaires-grid">
                            <?php
                            $jours_semaine = [
                                1 => ['nom' => 'Lundi', 'abbr' => 'Lun'],
                                2 => ['nom' => 'Mardi', 'abbr' => 'Mar'],
                                3 => ['nom' => 'Mercredi', 'abbr' => 'Mer'],
                                4 => ['nom' => 'Jeudi', 'abbr' => 'Jeu'],
                                5 => ['nom' => 'Vendredi', 'abbr' => 'Ven'],
                                6 => ['nom' => 'Samedi', 'abbr' => 'Sam'],
                                7 => ['nom' => 'Dimanche', 'abbr' => 'Dim']
                            ];

                            $aujourdhui_num = (int) date('N');

                            for ($i = 1; $i <= 7; $i++):
                                $dispo = $dispos[$i] ?? null;
                                $est_actif = $dispo && $dispo['actif'];
                                // ✅ CORRECTION — affiche tous les créneaux du jour
                                if ($est_actif && !empty($dispo['creneaux'])) {
                                    $heures_list = [];
                                    foreach ($dispo['creneaux'] as $c) {
                                        $heures_list[] = $c['debut'] . ' – ' . $c['fin'];
                                    }
                                    $heures = implode(' | ', $heures_list);
                                    // Résultat : "09:00 – 12:00 | 14:00 – 17:00"
                                } else {
                                    $heures = 'Fermé';
                                }
                                // Calculer la date du prochain jour correspondant
                                $decalage = ($i - $aujourdhui_num + 7) % 7;
                                $date_cliquable = date('Y-m-d', strtotime("+$decalage days"));
                                $est_selectionne = ($date_cliquable == $date_selectionnee);
                                ?>
                                <a href="?date=<?= $date_cliquable ?>&mois=<?= $mois ?>&annee=<?= $annee ?>"
                                    class="horaire-card <?= $est_actif ? 'active' : 'inactive' ?> <?= $est_selectionne ? 'selected' : '' ?>">
                                    <div class="horaire-jour"><?= $jours_semaine[$i]['nom'] ?></div>
                                    <div class="horaire-heures"><?= $heures ?></div>
                                    <?php if ($est_selectionne): ?>
                                        <div class="horaire-check">✓</div>
                                    <?php endif; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <p>Aucun médecin trouvé.</p>
                <?php endif; ?>
            </div>


            <!-- Calendrier -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon">📅</div>
                    <h2>Calendrier</h2>
                </div>
                <div class="calendar-header">
                    <div class="calendar-month"><?= $mois_fr[$mois] . ' ' . $annee ?></div>
                    <div class="calendar-nav">
                        <a href="?mois=<?= $mois_prev ?>&annee=<?= $annee_prev ?>&date=<?= $date_selectionnee ?>"><button
                                class="cal-nav-btn">‹</button></a>
                        <a href="?mois=<?= $mois_suiv ?>&annee=<?= $annee_suiv ?>&date=<?= $date_selectionnee ?>"><button
                                class="cal-nav-btn">›</button></a>
                    </div>

                </div>
                <table class="calendar-grid">
                    <thead>
                        <tr><?php foreach ($jours_fr as $j): ?>
                                <th><?= $j ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $premier_jour = (int) date('N', mktime(0, 0, 0, $mois, 1, $annee));
                        $nb_jours = (int) date('t', mktime(0, 0, 0, $mois, 1, $annee));
                        for ($ligne = 0; $ligne < 6; $ligne++):
                            $all_outside = true;
                            for ($c = 0; $c < 7; $c++) {
                                $d = 1 - ($premier_jour - 1) + $ligne * 7 + $c;
                                if ($d >= 1 && $d <= $nb_jours) {
                                    $all_outside = false;
                                    break;
                                }
                            }
                            if ($all_outside && $ligne > 0)
                                break;
                            ?>
                            <tr>
                                <?php for ($col = 0; $col < 7; $col++):
                                    $d = 1 - ($premier_jour - 1) + $ligne * 7 + $col;
                                    $is_cur = ($d >= 1 && $d <= $nb_jours);
                                    $date_cell = $is_cur ? sprintf('%04d-%02d-%02d', $annee, $mois, $d) : '';
                                    $is_today = ($date_cell === date('Y-m-d'));
                                    $is_sel = ($date_cell === $date_selectionnee);
                                    $has_rdv = $is_cur && in_array($d, $jours_avec_rdv);
                                    $display = $is_cur ? $d : ($d < 1 ? $nb_jours + $d : $d - $nb_jours);
                                    ?>
                                    <td>
                                        <div
                                            class="cal-day <?= $is_today ? 'today' : '' ?> <?= $has_rdv ? 'has-rdv' : '' ?> <?= $is_sel ? 'selected' : '' ?>">
                                            <?php if ($is_cur && $date_cell): ?>
                                                <a
                                                    href="?date=<?= $date_cell ?>&mois=<?= $mois ?>&annee=<?= $annee ?>"><?= $display ?></a>
                                            <?php else: ?>
                                                <span class="other-month"><?= $display ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                <?php endfor; ?>
                            </tr>

                        <?php endfor; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- Stats du jour -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-regular fa-calendar-check"
                    style="font-size: 25px;color: var(--blue-main);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                <div>
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">RDV du jour</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-circle-check"
                    style="font-size: 25px;color: var(--green-success);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                <div>
                    <div class="stat-number"><?= $stats['confirmé'] ?></div>
                    <div class="stat-label">Confirmés</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-hourglass-half"
                    style="font-size: 25px;color: var(--orange-warning);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                <div>
                    <div class="stat-number"><?= $stats['attente'] ?></div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-circle-xmark"
                    style="font-size: 25px;color: var(--red-alert);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                <div>
                    <div class="stat-number"><?= $stats['annulé'] ?></div>
                    <div class="stat-label">Annulés</div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="agenda-card">
            <div class="agenda-header">
                <div class="agenda-date-section">
                    <i class="fas fa-calendar-plus"
                        style="font-size: 22px;color: var(--blue-main);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                    <div class="agenda-date-title">
                        <?= dateLongFR($date_selectionnee) ?>
                    </div>
                    <div class="agenda-nav">
                        <a href="?date=<?= $date_prev ?>&mois=<?= $mois ?>&annee=<?= $annee ?>"><button
                                class="agenda-nav-btn">‹ Préc.</button></a>
                        <a href="?date=<?= date('Y-m-d') ?>&mois=<?= (int) date('m') ?>&annee=<?= (int) date('Y') ?>"><button
                                class="agenda-nav-btn today-btn">Aujourd'hui</button></a>
                        <a href="?date=<?= $date_suiv ?>&mois=<?= $mois ?>&annee=<?= $annee ?>"><button
                                class="agenda-nav-btn">Suiv. ›</button></a>
                    </div>
                </div>
                <button class="btn-new-rdv" onclick="ouvrirModal('<?= htmlspecialchars($date_selectionnee) ?>')">+
                    Nouveau
                    RDV</button>
            </div>

            <div class="timeline">
                <?php foreach ($creneaux as $creneau):
                    $rdv = $rdvs_par_heure[$creneau] ?? null;
                    $disponible = creneau_disponible($creneau, $dow, $dispos);
                    $show_time = (substr($creneau, 3) === '00');
                    ?>
                    <div class="timeline-slot">
                        <div class="time-label"><?= $show_time ? $creneau : '' ?></div>
                        <div class="slot-content">
                            <?php if ($rdv): ?>
                                <div class="rdv-item"
                                    onclick="voirRdv(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['bebe_nom']) ?>', '<?= $rdv['statut'] ?>', '<?= $rdv['type'] ?>', '<?= htmlspecialchars($rdv['motif'] ?? '') ?>')">
                                    <strong><?= $type_icons[$rdv['type']] ?? '🩺' ?>
                                        <?= htmlspecialchars($rdv['bebe_nom']) ?></strong>
                                    <br>
                                    <small><?= age_patient($rdv['date_naissance']) ?> ·
                                        <?= substr($rdv['heure_debut'], 0, 5) ?>–<?= substr($rdv['heure_fin'], 0, 5) ?> ·
                                        <?= $rdv['statut'] ?></small>
                                </div>
                            <?php elseif ($disponible): ?>
                                <div class="add-rdv-hint"
                                    onclick="ouvrirModal('<?= htmlspecialchars($date_selectionnee) ?>', '<?= $creneau ?>')">+
                                    Ajouter un RDV</div>
                            <?php else: ?>
                                <!-- Ne rien afficher du tout pour les créneaux indisponibles -->
                                <div style="height: 100%;"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ======== MODAL Nouveau RDV ======== -->
    <div id="modalOverlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div
            style="background:white; border-radius:16px; padding:24px; width:450px; max-width:90%; margin:100px auto;border:solid 1px #2b6e9e;background: white;border: radius 20px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; ">
                <h3><i class="fas fa-calendar-day"
                        style="font-size: 20px;color: var(--blue-main);background-color: #eff6ff;padding: 10px;border-radius: 8px;"></i>
                    Nouveau rendez-vous</h3>
                <button onclick="fermerModal()"
                    style="background:none; border:none; font-size:20px; cursor:pointer;">×</button>
            </div>
            <div id="modal-alert"></div>
            <form id="formNouveauRdv">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;color:#2b6e9e;">Patient</label>
                    <select name="bebe_id"
                        style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;border:solid 1px #2b6e9e;"
                        required>
                        <option value="">-- Sélectionner un patient --</option>
                        <?php foreach ($bebes as $b): ?>
                            <option value="<?= $b['id_bebe'] ?>"><?= htmlspecialchars($b['nom_prenom']) ?>
                                (<?= age_patient($b['date_naissance']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;color:#2b6e9e;">Date</label>
                        <input type="date" name="date_rdv" id="modal-date"
                            value="<?= htmlspecialchars($date_selectionnee) ?>"
                            style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;border:solid 1px #2b6e9e;"
                            required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;color:#2b6e9e;">Heure
                            début</label>
                        <input type="time" name="heure_debut" id="modal-heure" value="09:00"
                            style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;border:solid 1px #2b6e9e;"
                            required>
                    </div>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;color:#2b6e9e;">Type</label>
                    <select name="type"
                        style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;border:solid 1px #2b6e9e;">
                        <option value="consult"><i class="fas fa-stethoscope"></i> Consultation</option>
                        <option value="vaccin"><i class="fas fa-syringe"></i> Vaccin</option>
                        <option value="urgence"><i class="fas fa-ambulance"></i> Urgence !</option>
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;color:#2b6e9e;">Motif</label>
                    <input type="text" name="motif"
                        style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;border:solid 1px #2b6e9e;"
                        placeholder="Ex: Vaccination">
                </div>
                <input type="hidden" name="action" value="nouveau_rdv">
                <button type="submit"
                    style="width:100%; padding:10px; background: var(--blue-main); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Confirmer
                    le rendez-vous</button>
            </form>
        </div>
    </div>

    <!-- ======== MODAL Détail RDV ======== -->
    <div id="modalDetail"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; border-radius:16px; padding:24px; width:400px; max-width:90%; margin:100px auto;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 id="detail-title">Détail du rendez-vous</h3>
                <button onclick="fermerModalDetail()"
                    style="background:none; border:none; font-size:20px; cursor:pointer;">×</button>
            </div>
            <div id="detail-body" style="margin-bottom:20px; line-height:1.8;"></div>
            <div style="display:flex; gap:10px;">
                <button id="btn-confirmer"
                    style="flex:1; padding:10px; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer;"><i
                        class="fas fa-check"></i> Confirmer</button>
                <button id="btn-annuler"
                    style="flex:1; padding:10px; background:#ef4444; color:white; border:none; border-radius:6px; cursor:pointer;"><i
                        class="fas fa-times"></i> Annuler</button>
            </div>
        </div>
    </div>

    <script>
        function ouvrirModal(date = '', heure = '') {
            document.getElementById('modalOverlay').style.display = 'block';
            if (date) document.getElementById('modal-date').value = date;
            if (heure) document.getElementById('modal-heure').value = heure;
        }
        function fermerModal() {
            document.getElementById('modalOverlay').style.display = 'none';
            document.getElementById('formNouveauRdv').reset();
        }

        function voirRdv(id, patient, statut, type, motif) {
            const typesLabels = { consult: '🩺 Consultation', suivi: '📈 Suivi', urgence: '🚨 Urgence' };
            const statutLabels = { 'confirmé': '✅ Confirmé', 'attente': '⏳ En attente', 'annulé': '❌ Annulé' };
            document.getElementById('detail-title').textContent = 'RDV — ' + patient;
            document.getElementById('detail-body').innerHTML =
                '<b>Patient :</b> ' + patient + '<br>' +
                '<b>Type :</b> ' + (typesLabels[type] || type) + '<br>' +
                '<b>Statut :</b> ' + (statutLabels[statut] || statut) + '<br>' +
                '<b>Motif :</b> ' + (motif || '—');
            document.getElementById('btn-confirmer').onclick = () => changerStatut(id, 'confirmer_rdv');
            document.getElementById('btn-annuler').onclick = () => changerStatut(id, 'annuler_rdv');
            document.getElementById('modalDetail').style.display = 'block';
        }
        function fermerModalDetail() {
            document.getElementById('modalDetail').style.display = 'none';
        }

        async function changerStatut(id, action) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('rdv_id', id);
            const res = await fetch('agenda.php', { method: 'POST', body: fd });

            const data = await res.json();
            fermerModalDetail();
            alert(data.message);
            if (data.success) setTimeout(() => location.reload(), 1000);
        }

        document.getElementById('formNouveauRdv').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.textContent = 'Enregistrement...';
            const formData = new FormData(this);
            try {
                const res = await fetch('agenda.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    fermerModal();
                    alert(data.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    document.getElementById('modal-alert').innerHTML = '<div style="background:#fee2e2; color:#dc2626; padding:10px; border-radius:6px; margin-bottom:15px;">⚠ ' + data.message + '</div>';
                }
            } catch (err) {
                document.getElementById('modal-alert').innerHTML = '<div style="background:#fee2e2; color:#dc2626; padding:10px; border-radius:6px; margin-bottom:15px;">⚠ Erreur réseau</div>';
            }
            btn.disabled = false;
            btn.textContent = 'Confirmer le rendez-vous';
        });
        // ══════════════════════════════════════
        //  NOTIFICATIONS
        // ══════════════════════════════════════
        loadNotifications();
        setInterval(loadNotifications, 30000);

        function loadNotifications() {
            fetch('get_notifications_pediatre.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const nb = data.nb;
                    const countEl = document.getElementById('notif-count');
                    const badgeTxt = document.getElementById('notif-badge-txt');

                    if (nb > 0) { countEl.textContent = nb; countEl.style.display = 'flex'; countEl.style.animation = 'pulse 1.5s infinite'; }
                    else { countEl.style.display = 'none'; }
                    badgeTxt.textContent = nb + ' en attente';

                    const list = document.getElementById('notif-list');
                    if (nb === 0) {
                        list.innerHTML = '<div class="notif-empty"><i class="fas fa-check-circle"></i>Aucune demande en attente</div>';
                        return;
                    }
                    list.innerHTML = data.rdvs.map(rdv => {
                        const age = ageMoisFR(rdv.age_mois);
                        const dateStr = rdv.date_rdv.split('-').reverse().join('/');
                        const heure = rdv.heure_debut ? rdv.heure_debut.substring(0, 5) : '';
                        return `
                <div class="notif-item" id="notif-rdv-${rdv.id}">
                    <div class="notif-item-top">
                        <div>
                            <div class="notif-bebe"><i class="fas fa-baby" style="font-size:10px;margin-right:4px"></i>${rdv.bebe_nom}</div>
                            <div class="notif-parent">Parent : ${rdv.parent_nom} · ${age}</div>
                        </div>
                        <div class="notif-date-pill">${dateStr} à ${heure}</div>
                    </div>
                    ${rdv.motif ? `<div class="notif-motif"><i class="fas fa-notes-medical" style="font-size:10px"></i> ${rdv.motif}</div>` : ''}
                    <div class="notif-actions">
                        <button class="btn-notif-refuse" onclick="openRdvModal(${rdv.id},'${rdv.bebe_nom}','${rdv.parent_nom}','${dateStr}','${heure}',event)">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                        <button class="btn-notif-accept" onclick="actionDirecte(${rdv.id},'accepter',event)">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </div>
                </div>`;
                    }).join('');
                })
                .catch(err => console.error('Erreur notifications:', err));
        }

        // Toggle panneau
        document.getElementById('btn-notif').addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('notif-panel').classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            const panel = document.getElementById('notif-panel');
            if (!panel.contains(e.target) && e.target.id !== 'btn-notif')
                panel.classList.remove('open');
        });
        // ── Fonction manquante : formater l'âge en mois
        function ageMoisFR(mois) {
            if (mois === null || mois === undefined) return '—';
            if (mois < 1) return 'Nouveau-né';
            if (mois < 24) return mois + ' mois';
            const ans = Math.floor(mois / 12);
            return ans + ' an' + (ans > 1 ? 's' : '');
        }

        // ── Action directe : accepter ou refuser un RDV
        async function actionDirecte(rdv_id, action, e) {
            if (e) e.stopPropagation();
            const fd = new FormData();
            fd.append('action', action === 'accepter' ? 'confirmer_rdv' : 'annuler_rdv');
            fd.append('rdv_id', rdv_id);
            try {
                const res = await fetch('agenda_pediatre.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    // Retire visuellement la notif
                    const el = document.getElementById('notif-rdv-' + rdv_id);
                    if (el) el.remove();
                    // Recharge le compteur
                    loadNotifications();
                } else {
                    alert(data.message || 'Erreur');
                }
            } catch (err) {
                console.error('actionDirecte error:', err);
            }
        }

        // ── Ouvrir modal de refus (avec confirmation)
        function openRdvModal(rdv_id, bebe_nom, parent_nom, dateStr, heure, e) {
            if (e) e.stopPropagation();
            const confirme = confirm(
                `Refuser le RDV de ${bebe_nom} (${parent_nom})\nle ${dateStr} à ${heure} ?`
            );
            if (confirme) actionDirecte(rdv_id, 'refuser');
        }
    </script>

</body>

</html>

<?php $conn->close(); ?>