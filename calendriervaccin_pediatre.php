<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: connexion.php");
    exit;
}

$pediatre_id = (int) $_SESSION['user_id'];  // ← une seule ligne, plus de doublon
$conn = new mysqli("localhost", "root", "", "tinysteps");
if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}
$pediatre_id = (int) $_SESSION['user_id'];
$pediatre_id = isset($_SESSION['pediatre_id']) ? (int) $_SESSION['pediatre_id'] : 1;

// Infos du pédiatre
$res_ped = $conn->query("SELECT * FROM medecin WHERE id = $pediatre_id");
$pediatre = $res_ped->fetch_assoc();

// ══════════════════════════════════════
//  BÉBÉ SÉLECTIONNÉ (parmi ses patients)
// ══════════════════════════════════════
$bebe_id = isset($_GET['bebe_id']) ? (int) $_GET['bebe_id'] : 0;

// Vérifier que ce bébé appartient bien au pédiatre connecté
if ($bebe_id) {
    $check = $conn->query("SELECT id_bebe FROM bebe WHERE id_bebe = $bebe_id AND pediatre_id = $pediatre_id");
    if (!$check || $check->num_rows === 0) {
        $bebe_id = 0; // Accès refusé → reset
    }
}
// Liste des bébés du pédiatre
$res_bebes = $conn->query("SELECT * FROM bebe WHERE pediatre_id = $pediatre_id ORDER BY date_naissance DESC");
$bebes = [];
while ($b = $res_bebes->fetch_assoc())
    $bebes[] = $b;

// Si aucun bébé sélectionné, prendre le premier
if (!$bebe_id && !empty($bebes))
    $bebe_id = $bebes[0]['id_bebe'];

// Infos du bébé sélectionné
$bebe = null;
foreach ($bebes as $b) {
    if ($b['id_bebe'] == $bebe_id) {
        $bebe = $b;
        break;
    }
}

// Âge en mois
$age_mois = 0;
if ($bebe) {
    $dob = new DateTime($bebe['date_naissance']);
    $today = new DateTime();
    $diff = $dob->diff($today);
    $age_mois = $diff->y * 12 + $diff->m;
}
// ══════════════════════════════════════
//  FONCTION : parser l'âge → nb de mois
// ══════════════════════════════════════
function ageMoisDepuisLabel($label)
{
    $label = strtolower(trim($label));
    if ($label === 'naissance')
        return 0;
    if (preg_match('/^(\d+)\s*mois$/', $label, $m))
        return (int) $m[1];
    if (preg_match('/^(\d+)\s*an[s]?$/', $label, $m))
        return (int) $m[1] * 12;
    return 0;
}

// ══════════════════════════════════════
//  REQUÊTE VACCINS
// ══════════════════════════════════════
$today_str = date('Y-m-d');
$all_vaccs = [];
$data = [];

if ($bebe_id) {
    $sql = "
    SELECT c.age, v.id AS vaccin_id, v.nom, v.maladies,
           vac.id AS vaccination_id,
           vac.date_prevue, vac.date_effectuee, vac.statut,
           vac.note_medicale
    FROM calendrier c
    JOIN vaccin v ON c.vaccin_id = v.id
    LEFT JOIN vaccination vac ON vac.vaccin_id = v.id AND vac.bebe_id = $bebe_id
    ORDER BY c.id
    ";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $age_label = $row['age'];

        // ── Calcul dynamique de date_prevue si absente ──
        $date_prevue = $row['date_prevue'];
        if (empty($date_prevue) && $bebe) {
            $nb_mois = ageMoisDepuisLabel($age_label);
            $dob = new DateTime($bebe['date_naissance']);
            $dob->modify("+{$nb_mois} months");
            $date_prevue = $dob->format('Y-m-d');
        }

        // ── Calcul du statut ──
        if (!empty($row['date_effectuee'])) {
            $statut = 'done';
        } elseif (!empty($date_prevue) && $date_prevue < $today_str) {
            $statut = 'overdue';
        } elseif (!empty($date_prevue)) {
            $diff_days = (strtotime($date_prevue) - strtotime($today_str)) / 86400;
            $statut = $diff_days <= 30 ? 'upcoming' : 'future';
        } else {
            $statut = 'future';
        }

        $vacc = [
            'vaccin_id' => $row['vaccin_id'],
            'vaccination_id' => $row['vaccination_id'],
            'nom' => $row['nom'],
            'maladies' => $row['maladies'] ?? '',
            'age' => $age_label,
            'statut' => $statut,
            'date_prevue' => $date_prevue,        // ← date calculée
            'date_effectuee' => $row['date_effectuee'] ?? '',
            'note_medicale' => $row['note_medicale'] ?? '',
        ];

        $data[$age_label][] = $vacc;
        $all_vaccs[] = $vacc;
    }
}
// ── Stats
$total = count($all_vaccs);
$done = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'done'));
$upcoming = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'upcoming'));
$overdue = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'overdue'));
$future = count(array_filter($all_vaccs, fn($v) => $v['statut'] === 'future'));
$pct = $total > 0 ? round(($done / $total) * 100) : 0;

// ── Filtre URL
$filtre = $_GET['filtre'] ?? 'all';

// ── Helpers
function dateFR($d)
{
    if (!$d)
        return '—';
    return date('d/m/Y', strtotime($d));
}
function statutLabel($s)
{
    return match ($s) {
        'done' => '<i class="fas fa-check-circle"></i> Effectué',
        'upcoming' => '<i class="fas fa-clock"></i> Imminent',
        'overdue' => '<i class="fas fa-times-circle"></i> En retard',
        default => '<i class="fas fa-calendar-alt"></i> À venir'
    };
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinySteps — Espace Pédiatre · Vaccins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles\calendrier_pediatre.css">

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
            <a href="liste_patients.php" ><i class="fas fa-baby"></i> Mes patients</a>
            <a href="calendriervaccin_pediatre.php" class="active"><i class="fas fa-syringe"></i> Calendrier
                vaccinal</a>
            <a href="agenda.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
            <a href="profil_pediatre.php"><i class="fas fa-user-md"></i> Mon profil</a>
        </nav>
        <div class="sidebar-footer">
            <div class="doctor-badge">
                <div class="doctor-avatar">
                    <?= strtoupper(substr($pediatre['nom'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <a href="recherche_pediatre.php" class="doctor-name" style="text-decoration:none;color:inherit;">Dr.
                        <?= htmlspecialchars($pediatre['nom'] ?? 'Pédiatre') ?>
                    </a>
                    <div class="doctor-role">Pédiatre</div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>


    <!-- ══ PAGE WRAPPER ══ -->
    <div class="page-wrapper">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="fas fa-syringe"></i> Calendrier Vaccinal</h1>
                <p>Programme National d'Immunisation — Suivi de vos patients</p>
            </div>
            <div class="topbar-actions">

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
                <button class="icon-btn" title="Aide"><i class="fas fa-question-circle"></i></button>
            </div>
        </div>

        <!-- Main -->
        <div class="main">

            <!-- Baby selector -->
            <?php if (!empty($bebes)): ?>
                <div class="baby-selector">
                    <span class="baby-selector-label">Patient :</span>
                    <?php foreach ($bebes as $b): ?>
                        <a href="?bebe_id=<?= $b['id_bebe'] ?>&filtre=<?= $filtre ?>"
                            class="baby-tab <?= $b['id_bebe'] == $bebe_id ? 'active' : '' ?>">
                            <?= htmlspecialchars($b['nom_prenom']) ?>
                            <?php
                            // Petit indicateur de retard
                            $n_overdue = 0;
                            if ($b['id_bebe'] == $bebe_id) {
                                $n_overdue = $overdue;
                            } else {
                                $r = $conn->query("SELECT COUNT(*) AS c FROM vaccination vac
                                       JOIN calendrier c ON c.vaccin_id = vac.vaccin_id
                                       WHERE vac.bebe_id = {$b['id_bebe']}
                                         AND vac.date_effectuee IS NULL
                                         AND vac.date_prevue < '$today_str'");
                                $n_overdue = $r ? $r->fetch_assoc()['c'] : 0;
                            }
                            if ($n_overdue > 0): ?>
                                <span style="display:inline-flex;align-items:center;justify-content:center;
                                 background:#E74C3C;color:#fff;border-radius:50%;width:16px;height:16px;
                                 font-size:10px;font-weight:700;margin-left:4px">
                                    <?= $n_overdue ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($all_vaccs)): ?>
                <div class="empty">
                    <div style="font-size:60px;margin-bottom:16px"></div>
                    <h3>Aucun patient sélectionné</h3>
                    <p>Sélectionnez un patient parmi vos bébés suivis pour voir son calendrier vaccinal.</p>
                </div>

            <?php else: ?>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--green-l)"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-val" style="color:var(--green)">
                                <?= $done ?>
                            </div>
                            <div class="stat-lbl">Effectués</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--amber-l)"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="stat-val" style="color:var(--amber)">
                                <?= $upcoming ?>
                            </div>
                            <div class="stat-lbl">Imminents</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--red-l)"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <div class="stat-val" style="color:var(--red)">
                                <?= $overdue ?>
                            </div>
                            <div class="stat-lbl">En retard</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--primary-l)"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="stat-val" style="color:var(--blue-main)">
                                <?= $age_mois ?>
                            </div>
                            <div class="stat-lbl">Mois de
                                <?= htmlspecialchars($bebe['prenom'] ?? 'bébé') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert overdue -->
                <?php
                $first_urgent = null;
                foreach ($all_vaccs as $v) {
                    if ($v['statut'] === 'overdue' || $v['statut'] === 'upcoming') {
                        $first_urgent = $v;
                        break;
                    }
                }
                if ($first_urgent):
                    $jours = $first_urgent['date_prevue']
                        ? (int) ((strtotime($first_urgent['date_prevue']) - strtotime($today_str)) / 86400)
                        : null;
                    ?>
                    <div class="alert-bar <?= $first_urgent['statut'] === 'upcoming' ? 'upcoming-alert' : '' ?>">
                        <div class="alert-icon">
                            <?= $first_urgent['statut'] === 'overdue' ? '<i class="fas fa-exclamation-circle"></i>'
                                : '<i class="fas fa-exclamation-triangle"></i>'
                                ?>
                        </div>

                        <div class="alert-content">
                            <div class="alert-label">
                                <?= $first_urgent['statut'] === 'overdue' ? 'Vaccin en retard' : 'Vaccin imminent' ?>
                            </div>
                            <div class="alert-title">
                                <?= htmlspecialchars($first_urgent['nom']) ?> —
                                <?= htmlspecialchars($first_urgent['age']) ?>
                            </div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                                Patient :
                                <?= htmlspecialchars($bebe['nom_prenom'] ?? '') ?> —
                                <?= $age_mois ?> mois
                            </div>
                        </div>
                        <?php if ($jours !== null): ?>
                            <div class="alert-days">
                                <div class="alert-days-num">
                                    <?= abs($jours) ?>
                                </div>
                                <div class="alert-days-lbl">
                                    <?= $jours < 0 ? 'jours retard' : 'jours restants' ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Progress -->
                <div class="progress-section">
                    <div class="prog-pct">
                        <?= $pct ?>%<br><span>complété</span>
                    </div>
                    <div class="prog-info">
                        <div class="prog-label">Progression vaccinale de
                            <?= htmlspecialchars($bebe['prenom'] ?? 'bébé') ?>
                        </div>
                        <div class="prog-track">
                            <div class="prog-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="prog-sub">
                            <?= $done ?> /
                            <?= $total ?> vaccins administrés
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <div class="legend">
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--green)"></div>Effectué
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:#D97706"></div>Imminent
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--red)"></div>En retard
                        </div>
                        <div class="leg">
                            <div class="leg-dot" style="background:var(--slate)"></div>À venir
                        </div>
                    </div>
                    <div class="filters">
                        <?php
                        $filtres = ['all' => 'Tous', 'done' => 'Effectués', 'upcoming' => 'Imminents', 'overdue' => 'En retard', 'future' => 'À venir'];
                        foreach ($filtres as $key => $label):
                            ?>
                            <a href="?bebe_id=<?= $bebe_id ?>&filtre=<?= $key ?>"
                                class="<?= $filtre === $key ? 'active' : '' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="timeline">
                    <?php
                    $modal_json = [];
                    $card_index = 0;

                    $statuts_priorite = ['overdue' => 0, 'upcoming' => 1, 'done' => 2, 'future' => 3];

                    foreach ($data as $age => $vaccins):
                        $vaccins_filtres = $filtre === 'all'
                            ? $vaccins
                            : array_filter($vaccins, fn($v) => $v['statut'] === $filtre);
                        if (empty($vaccins_filtres))
                            continue;

                        usort($vaccins_filtres, fn($a, $b) => $statuts_priorite[$a['statut']] <=> $statuts_priorite[$b['statut']]);
                        $vaccins_filtres = array_values($vaccins_filtres);
                        $statut_dominant = $vaccins_filtres[0]['statut'];
                        $count = count($vaccins_filtres);
                        ?>
                        <div class="tl-row">
                            <div class="tl-age">
                                <div class="tl-age-label">
                                    <?= htmlspecialchars($age) ?>
                                </div>
                            </div>
                            <div class="tl-dot-col">
                                <div class="tl-dot <?= $statut_dominant ?>"></div>
                            </div>
                            <div class="tl-cards">
                                <?php if ($count > 1): ?>
                                    <div class="vacc-grid">
                                    <?php endif; ?>

                                    <?php foreach ($vaccins_filtres as $v):
                                        $s = $v['statut'];
                                        $idx = $card_index++;
                                        $modal_json[$idx] = [
                                            'vaccin_id' => $v['vaccin_id'],
                                            'vaccination_id' => $v['vaccination_id'],
                                            'bebe_id' => $bebe_id,
                                            'nom' => $v['nom'],
                                            'age' => $v['age'],
                                            'statut' => $s,
                                            'date_prevue' => $v['date_prevue'],
                                            'date_effectuee' => $v['date_effectuee'],
                                            'note_medicale' => $v['note_medicale'],
                                            'maladies' => $v['maladies'],
                                        ];
                                        ?>
                                        <div class="vacc-card <?= $s ?>" id="card-<?= $idx ?>">
                                            <div class="vacc-top">
                                                <div>
                                                    <div class="vacc-name">
                                                        <?= htmlspecialchars($v['nom']) ?>
                                                    </div>
                                                    <div class="vacc-sub">
                                                        <?= htmlspecialchars($v['age']) ?>
                                                    </div>
                                                </div>
                                                <span class="status-badge <?= $s ?>">
                                                    <?= statutLabel($s) ?>
                                                </span>
                                            </div>
                                            <?php if ($v['maladies']): ?>
                                                <div class="vacc-tags">
                                                    <?php foreach (explode(',', $v['maladies']) as $m): ?>
                                                        <span class="vacc-tag">
                                                            <?= trim(htmlspecialchars($m)) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="vacc-footer">
                                                <span class="vacc-date">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <?= $s === 'done'
                                                        ? 'Administré le ' . dateFR($v['date_effectuee'])
                                                        : 'Prévu le ' . dateFR($v['date_prevue']) ?>
                                                </span>
                                                <div class="vacc-actions">
                                                    <?php if ($s === 'done'): ?>
                                                        <span class="btn-done-label">✓ Administré</span>
                                                        <button class="btn-detail" onclick="openDetail(<?= $idx ?>)">Détails</button>
                                                    <?php else: ?>
                                                        <button class="btn-mark-done" onclick="openValidModal(<?= $idx ?>)">
                                                            <i class="fas fa-check"></i> Marquer fait
                                                        </button>
                                                        <button class="btn-detail" onclick="openDetail(<?= $idx ?>)">Détails</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if ($count > 1): ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Source -->
                <div class="source">
                    <span style="font-size:24px">🇹🇳</span>
                    <span><strong>Source :</strong> Programme National d'Immunisation — Ministère de la Santé Tunisien 2024.
                        Tous les vaccins sont gratuits dans les Centres de Santé de Base (CSB).</span>
                </div>

            <?php endif; ?>
        </div><!-- /main -->
    </div><!-- /page-wrapper -->

    <!-- ══ MODAL DÉTAIL (lecture seule) ══ -->
    <div class="overlay" id="overlay-detail" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal">
            <div class="modal-head">
                <h2 id="d-title"></h2>
                <p id="d-sub"></p>
                <button class="modal-close"
                    onclick="document.getElementById('overlay-detail').classList.remove('open')">✕</button>
            </div>
            <div class="modal-body" id="d-body"></div>
            <div class="modal-foot">
                <button class="btn-secondary"
                    onclick="document.getElementById('overlay-detail').classList.remove('open')">Fermer</button>
                <button class="btn-primary" id="d-btn-edit" onclick="switchToValid()">
                    <i class="fas fa-syringe"></i> Marquer administré
                </button>
            </div>
        </div>
    </div>

    <!-- ══ MODAL VALIDATION VACCIN (action pédiatre) ══ -->
    <div class="overlay" id="overlay-valid" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal" style="max-width:500px">
            <div class="modal-head">
                <h2><i class="fas fa-check-circle"></i> Valider l'administration</h2>
                <p id="v-sub">Saisissez les informations d'administration du vaccin</p>
                <button class="modal-close"
                    onclick="document.getElementById('overlay-valid').classList.remove('open')">✕</button>
            </div>
            <div class="modal-body">
                <!-- Infos du vaccin (résumé) -->
                <div style="background:var(--primary-l);border-radius:10px;padding:12px 14px;margin-bottom:16px;">
                    <div style="font-size:13px;font-weight:700;color:var(--blue-main)" id="v-vacc-name"></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px" id="v-vacc-info"></div>
                </div>
                <!-- Formulaire -->
                <div class="form-row">
                    <label><i class="fas fa-calendar-day"></i> Date d'administration *</label>
                    <input type="date" id="v-date" required>
                </div>

                <div class="form-row">
                    <label><i class="fas fa-notes-medical"></i> Note médicale</label>
                    <textarea id="v-note" rows="3" placeholder="Réactions observées, remarques cliniques…"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn-secondary"
                    onclick="document.getElementById('overlay-valid').classList.remove('open')">Annuler</button>
                <button class="btn-success" onclick="submitValidation()">
                    <i class="fas fa-check-circle"></i> Confirmer l'administration
                </button>
            </div>
        </div>
    </div>

    <!-- ══ TOAST ══ -->
    <div class="toast-rdv" id="toast-rdv-el"></div>
    <script>
        let currentRdvId = null;

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
                        const heure = rdv.heure_rdv ? rdv.heure_rdv.substring(0, 5) : '';
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

        // ══════════════════════════════════════
        //  ACTIONS RDV
        // ══════════════════════════════════════
        function actionDirecte(rdvId, action, e) {
            e.stopPropagation();
            sendAction(rdvId, action, '');
        }

        function openRdvModal(rdvId, bebeNom, parentNom, date, heure, e) {
            if (e) e.stopPropagation();
            currentRdvId = rdvId;
            document.getElementById('rdv-modal-title').textContent = 'Gérer le rendez-vous';
            document.getElementById('rdv-info-box').innerHTML = `
        <strong>Patient :</strong> ${bebeNom}<br>
        <strong>Parent :</strong> ${parentNom}<br>
        <strong>Date :</strong> ${date} à ${heure}`;
            document.getElementById('rdv-message').value = '';
            document.getElementById('refus-row').style.display = 'none';
            document.getElementById('btn-refuser').dataset.confirm = '';
            document.getElementById('btn-refuser').innerHTML = '<i class="fas fa-times-circle"></i> Refuser';
            document.getElementById('overlay-rdv').classList.add('open');
        }

        function confirmAction(action) {
            if (action === 'refuser') {
                document.getElementById('refus-row').style.display = 'block';
                if (document.getElementById('btn-refuser').dataset.confirm === '1') {
                    sendAction(currentRdvId, 'refuser', document.getElementById('rdv-message').value);
                    closeRdvModal(); return;
                }
                document.getElementById('btn-refuser').dataset.confirm = '1';
                document.getElementById('btn-refuser').innerHTML = '<i class="fas fa-times-circle"></i> Confirmer le refus';
            } else {
                sendAction(currentRdvId, 'accepter', '');
                closeRdvModal();
            }
        }

        function closeRdvModal() {
            document.getElementById('overlay-rdv').classList.remove('open');
            currentRdvId = null;
        }

        function sendAction(rdvId, action, message) {
            const body = new FormData();
            body.append('rdv_id', rdvId);
            body.append('action', action);
            body.append('message', message);
            fetch('gerer_rdv.php', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const el = document.getElementById('notif-rdv-' + rdvId);
                        if (el) { el.style.transition = 'all .3s'; el.style.opacity = '0'; el.style.maxHeight = '0'; setTimeout(() => el.remove(), 300); }
                        showToast(action === 'accepter' ? '✅ RDV confirmé !' : '❌ RDV refusé.', action === 'accepter' ? 'success' : 'error');
                        setTimeout(() => { loadNotifications(); location.reload(); }, 1000);
                    } else {
                        showToast('Erreur : ' + (res.message || 'inconnue'), 'error');
                    }
                })
                .catch(() => showToast('Erreur réseau.', 'error'));
        }

        // ══════════════════════════════════════
        //  HELPERS
        // ══════════════════════════════════════
        function ageMoisFR(m) {
            if (m < 12) return m + ' mois';
            const ans = Math.floor(m / 12), mo = m % 12;
            return ans + ' an' + (ans > 1 ? 's' : '') + (mo ? ' ' + mo + ' mois' : '');
        }
        function showToast(msg, type = 'success') {
            let t = document.getElementById('toast-rdv-el');
            t.textContent = msg;
            t.className = 'toast-rdv ' + type;
            setTimeout(() => t.classList.add('show'), 10);
            setTimeout(() => t.classList.remove('show'), 3500);
        }
    </script>

    <!-- ══ DATA JSON ══ -->
    <script>
        const vaccData = <?= json_encode($modal_json) ?>;
        let currentIdx = null;

        // ── MODAL DÉTAIL
        function openDetail(idx) {
            const d = vaccData[idx];
            if (!d) return;
            currentIdx = idx;

            document.getElementById('d-title').textContent = d.nom;
            document.getElementById('d-sub').textContent = d.age;

            const rows = [
                ['Patient', '<?= htmlspecialchars($bebe['nom_prenom'] ?? '') ?>'],
                ['Statut', statutLabel(d.statut)],
                ['Date prévue', dateFR(d.date_prevue)],
                ['Date administrée', d.date_effectuee ? dateFR(d.date_effectuee) : '—'],
                ['Note médicale', d.note_medicale || '—'],
                ['Maladies cibles', d.maladies || '—'],
            ];

            document.getElementById('d-body').innerHTML = rows
                .map(r => `<div class="modal-row"><span class="lbl">${r[0]}</span><span class="val">${r[1]}</span></div>`)
                .join('');

            // Masquer le bouton "marquer administré" si déjà fait
            const btn = document.getElementById('d-btn-edit');
            btn.style.display = d.statut === 'done' ? 'none' : 'flex';

            document.getElementById('overlay-detail').classList.add('open');
        }

        // ── MODAL VALIDATION (depuis carte ou depuis détail)
        function openValidModal(idx) {
            const d = vaccData[idx];
            if (!d) return;
            currentIdx = idx;

            document.getElementById('v-vacc-name').textContent = d.nom;
            document.getElementById('v-vacc-info').textContent = d.age + ' — Patient : <?= htmlspecialchars($bebe['nom_prenom'] ?? '') ?> — <?= $age_mois ?> mois'; document.getElementById('v-date').value = new Date().toISOString().split('T')[0];

            document.getElementById('v-note').value = d.note_medicale || '';

            document.getElementById('overlay-valid').classList.add('open');
        }

        // Depuis modal détail → switch vers modal validation
        function switchToValid() {
            document.getElementById('overlay-detail').classList.remove('open');
            setTimeout(() => openValidModal(currentIdx), 200);
        }

        // ── SOUMETTRE LA VALIDATION
        function submitValidation() {
            const d = vaccData[currentIdx];
            if (!d) return;

            const date = document.getElementById('v-date').value;
            if (!date) { showToast('Veuillez saisir la date d\'administration.', true); return; }

            const body = new FormData();
            body.append('bebe_id', d.bebe_id);
            body.append('vaccin_id', d.vaccin_id);
            body.append('vaccination_id', d.vaccination_id || '');
            body.append('date_effectuee', date);

            body.append('note_medicale', document.getElementById('v-note').value);
            body.append('pediatre_id', <?= $pediatre_id ?>);

            fetch('marquer_vaccin.php', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Ferme modal
                        document.getElementById('overlay-valid').classList.remove('open');
                        // Met à jour la carte visuellement
                        updateCard(currentIdx, date);
                        // Met à jour les données locales
                        vaccData[currentIdx].statut = 'done';
                        vaccData[currentIdx].date_effectuee = date;
                        showToast(' Vaccin marqué comme administré !');
                    } else {
                        showToast('Erreur : ' + (res.message || 'inconnue'), true);
                    }
                })
                .catch(() => showToast('Erreur réseau.', true));
        }

        // ── MET À JOUR LA CARTE SANS RECHARGEMENT
        function updateCard(idx, date) {
            const card = document.getElementById('card-' + idx);
            if (!card) return;

            // Badge statut
            const badge = card.querySelector('.status-badge');
            if (badge) { badge.className = 'status-badge done'; badge.innerHTML = '<i class="fas fa-check-circle"></i> Effectué'; }

            // Bordure gauche
            card.className = card.className.replace(/\b(upcoming|overdue|future)\b/, 'done');

            // Date
            const dateEl = card.querySelector('.vacc-date');
            if (dateEl) dateEl.innerHTML = '<i class="fas fa-calendar-alt"></i> Administré le ' + dateFR(date);

            // Boutons
            const actions = card.querySelector('.vacc-actions');
            if (actions) {
                actions.innerHTML = `
            <span class="btn-done-label">✓ Administré</span>
            <button class="btn-detail" onclick="openDetail(${idx})">Détails</button>
        `;
            }
        }

        // ── HELPERS JS
        function statutLabel(s) {
            const m = { done: '<i class="fas fa-check-circle"></i> Effectué', upcoming: '<i class="fas fa-clock"></i> Imminent', overdue: '<i class="fas fa-times-circle"></i> En retard', future: '<i class="fas fa-calendar-alt"></i> À venir' };
            return m[s] || s;
        }
        function dateFR(d) {
            if (!d) return '—';
            const [y, mo, day] = d.split('-');
            return `${day}/${mo}/${y}`;
        }

        // ── TOAST
        function showToast(msg, error = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast' + (error ? ' error' : '');
            setTimeout(() => t.classList.add('show'), 10);
            setTimeout(() => t.classList.remove('show'), 3500);
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>